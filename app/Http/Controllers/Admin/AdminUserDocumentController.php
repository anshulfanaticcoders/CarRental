<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\UserDocument;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;

class AdminUserDocumentController extends Controller
{
    /**
     * Display a listing of user documents.
     */
    public function index(Request $request): Response
    {
        $query = UserDocument::query()
            ->with(['user:id,first_name,last_name,email']);

        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            })
            ->orWhere('document_number', 'like', "%{$search}%")
            ->orWhere('document_type', 'like', "%{$search}%");
        }

        // Filter by status if provided
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('verification_status', $request->status);
        }

        // Get paginated results
        $documents = $query->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('AdminDashboardPages/UserDocument/Index', [
            'documents' => $documents,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    /**
     * Update the specified document's verification status.
     */
    public function update(Request $request, UserDocument $userDocument)
    {
        $request->validate([
            'verification_status' => 'required|in:pending,verified,rejected',
            'expires_at' => 'nullable|date',
        ]);

        $data = [
            'verification_status' => $request->verification_status,
            'expires_at' => $request->expires_at ? date('Y-m-d', strtotime($request->expires_at)) : null,
        ];

        // If status is changing to verified, set the verified_at timestamp
        if ($request->verification_status === 'verified' && $userDocument->verification_status !== 'verified') {
            $data['verified_at'] = Carbon::now();
        }

        // If status is changing from verified to something else, clear the verified_at timestamp
        if ($request->verification_status !== 'verified' && $userDocument->verification_status === 'verified') {
            $data['verified_at'] = null;
        }

        $userDocument->update($data);

        return redirect()->back()->with('success', 'Document verification status updated successfully.');
    }

    /**
     * Show a specific document.
     */
    public function show(UserDocument $userDocument): Response
    {
        $userDocument->load('user:id,first_name,last_name,email');
        
        return Inertia::render('AdminDashboardPages/UserDocument/Show', [
            'document' => $userDocument,
        ]);
    }

    /**
     * Get statistics for the dashboard.
     */
    public function stats()
    {
        $stats = [
            'total' => UserDocument::count(),
            'pending' => UserDocument::where('verification_status', 'pending')->count(),
            'verified' => UserDocument::where('verification_status', 'verified')->count(),
            'rejected' => UserDocument::where('verification_status', 'rejected')->count(),
            'expiring_soon' => UserDocument::where('verification_status', 'verified')
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', Carbon::now()->addDays(30))
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Get recent document submissions.
     */
    public function recent()
    {
        $recentDocuments = UserDocument::with(['user:id,first_name,last_name,email'])
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        return response()->json($recentDocuments);
    }
}