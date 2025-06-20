<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Notification;
use App\Models\ContactSubmission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Notifications\ContactUsNotification;
use App\Notifications\ContactUsUserConfirmation;
use Inertia\Inertia;
use Inertia\Response;


class ContactFormController extends Controller
{
    public function store(Request $request)
    {
        // Validate the form submission
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'message' => 'required|string|max:2000'
        ]);

        // If validation fails, return back with errors
        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        // Create the contact submission
        $submission = ContactSubmission::create([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'message' => $request->input('message')
        ]);

        $admin = User::where('email', $request->input('admin_email'))->first();
        if ($admin) {
            $admin->notify(new ContactUsNotification($submission));
        }
    
        // Notify the user (email string)
        Notification::route('mail', $request->input('email'))
            ->notify(new ContactUsUserConfirmation($submission));
     

        // Redirect back with a success message
        return back()->with('success', 'Your message has been sent successfully!');
    }

    // Optional: Add a method to list submissions in admin panel
    public function index()
    {
        $submissions = ContactSubmission::latest()->paginate(20);
        return view('admin.contact-submissions', compact('submissions'));
    }

    public function fetchSubmissions(): Response
    {
        $submissions = ContactSubmission::latest()->paginate(9); // Changed to paginate

        return Inertia::render('MailServices/ContactUsMails/Index', [
            'submissions' => $submissions // This will now be a paginator instance
        ]);
    }


    public function unreadNotifications()
    {
        return response()->json([
            'unread_count' => Auth::user()->unreadNotifications->count(),
            'notifications' => Auth::user()->unreadNotifications
        ]);
    }

    public function markAsRead($id)
    {
        $notification = Auth::user()->notifications()->where('id', $id)->first();

        if ($notification) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }
}
