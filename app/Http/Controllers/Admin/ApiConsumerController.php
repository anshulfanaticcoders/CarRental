<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiBooking;
use App\Models\ApiConsumer;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiConsumerController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $query = ApiConsumer::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('contact_email', 'like', "%{$search}%");
            });
        }

        $consumers = $query->withCount(['activeKeys'])
            ->withCount(['apiLogs as total_requests'])
            ->withMax('apiKeys', 'last_used_at')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('AdminDashboardPages/ApiConsumers/Index', [
            'consumers' => $consumers,
            'filters' => ['search' => $search],
        ]);
    }

    public function create()
    {
        return Inertia::render('AdminDashboardPages/ApiConsumers/Create', [
            'plans' => ['basic', 'premium', 'enterprise'],
            'defaultRateLimit' => ['basic' => 60, 'premium' => 500, 'enterprise' => 1000],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'contact_name' => 'required|max:255',
            'contact_email' => 'required|email|unique:api_consumers,contact_email',
            'contact_phone' => 'nullable|max:50',
            'company_url' => 'nullable|url|max:255',
            'plan' => 'required|in:basic,premium,enterprise',
            'mode' => 'required|in:sandbox,live',
            'rate_limit' => 'required|integer|min:1',
            'notes' => 'nullable',
        ]);

        $consumer = ApiConsumer::create($validated);

        $result = $this->createApiKey($consumer);

        return redirect()->route('admin.api-consumers.show', $consumer)
            ->with('success', 'API Consumer created successfully.')
            ->with('newApiKey', $result['plaintext']);
    }

    public function show(ApiConsumer $apiConsumer)
    {
        $apiConsumer->load([
            'apiKeys' => fn ($q) => $q->latest(),
            'apiLogs' => fn ($q) => $q->latest('created_at')->limit(50),
        ]);

        $totalBookings = ApiBooking::where('api_consumer_id', $apiConsumer->id)->count();

        $now = now();
        $requestsToday = $apiConsumer->apiLogs()->where('created_at', '>=', $now->copy()->startOfDay())->count();
        $requestsThisWeek = $apiConsumer->apiLogs()->where('created_at', '>=', $now->copy()->startOfWeek())->count();
        $requestsThisMonth = $apiConsumer->apiLogs()->where('created_at', '>=', $now->copy()->startOfMonth())->count();

        return Inertia::render('AdminDashboardPages/ApiConsumers/Show', [
            'consumer' => $apiConsumer,
            'apiKeys' => $apiConsumer->apiKeys,
            'apiLogs' => $apiConsumer->apiLogs,
            'stats' => [
                'today' => $requestsToday,
                'week' => $requestsThisWeek,
                'month' => $requestsThisMonth,
                'total_bookings' => $totalBookings,
            ],
            'newKey' => session('newApiKey'),
        ]);
    }

    public function edit(ApiConsumer $apiConsumer)
    {
        return Inertia::render('AdminDashboardPages/ApiConsumers/Edit', [
            'consumer' => $apiConsumer,
            'plans' => ['basic', 'premium', 'enterprise'],
            'defaultRateLimit' => ['basic' => 60, 'premium' => 500, 'enterprise' => 1000],
        ]);
    }

    public function update(Request $request, ApiConsumer $apiConsumer)
    {
        $validated = $request->validate([
            'name' => 'required|max:255',
            'contact_name' => 'required|max:255',
            'contact_email' => 'required|email|unique:api_consumers,contact_email,' . $apiConsumer->id,
            'contact_phone' => 'nullable|max:50',
            'company_url' => 'nullable|url|max:255',
            'plan' => 'required|in:basic,premium,enterprise',
            'mode' => 'required|in:sandbox,live',
            'rate_limit' => 'required|integer|min:1',
            'notes' => 'nullable',
        ]);

        $apiConsumer->update($validated);

        return redirect()->route('admin.api-consumers.show', $apiConsumer)
            ->with('success', 'API Consumer updated successfully.');
    }

    public function destroy(ApiConsumer $apiConsumer)
    {
        $apiConsumer->delete();

        return redirect()->route('admin.api-consumers.index')
            ->with('success', 'API Consumer deleted successfully.');
    }

    public function generateKey(ApiConsumer $apiConsumer)
    {
        $result = $this->createApiKey($apiConsumer);

        return redirect()->back()
            ->with('success', 'New API key generated successfully.')
            ->with('newApiKey', $result['plaintext']);
    }

    public function rotateKey(ApiKey $apiKey)
    {
        $apiKey->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        $result = $this->createApiKey($apiKey->consumer, $apiKey->name, $apiKey->scopes);

        return redirect()->back()
            ->with('success', 'API key rotated successfully.')
            ->with('newApiKey', $result['plaintext']);
    }

    public function revokeKey(ApiKey $apiKey)
    {
        $apiKey->update([
            'status' => 'revoked',
            'revoked_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'API key revoked successfully.');
    }

    public function toggleStatus(ApiConsumer $apiConsumer)
    {
        $apiConsumer->update([
            'status' => $apiConsumer->status === 'active' ? 'suspended' : 'active',
        ]);

        return redirect()->back()
            ->with('success', 'Consumer status updated to ' . $apiConsumer->status . '.');
    }

    private function createApiKey(ApiConsumer $consumer, string $name = 'Default', ?array $scopes = null): array
    {
        $prefix = 'vrm_live_';
        $randomPart = bin2hex(random_bytes(20));
        $plaintextKey = $prefix . $randomPart;
        $hash = hash('sha256', $plaintextKey);

        $apiKey = ApiKey::create([
            'api_consumer_id' => $consumer->id,
            'key_hash' => $hash,
            'key_prefix' => substr($plaintextKey, 0, 12),
            'name' => $name,
            'status' => 'active',
            'scopes' => $scopes ?? ['locations:read', 'vehicles:search', 'vehicles:extras', 'bookings:create', 'bookings:read', 'bookings:cancel'],
        ]);

        return ['key' => $apiKey, 'plaintext' => $plaintextKey];
    }
}
