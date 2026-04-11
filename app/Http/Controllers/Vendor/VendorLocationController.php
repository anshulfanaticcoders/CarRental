<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\VendorLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class VendorLocationController extends Controller
{
    public function index()
    {
        $vendorId = auth()->id();

        $locations = VendorLocation::query()
            ->where('vendor_id', $vendorId)
            ->withCount('vehicles')
            ->orderBy('name')
            ->get()
            ->map(fn (VendorLocation $location) => [
                'id' => $location->id,
                'name' => $location->name,
                'code' => $location->code,
                'address_line_1' => $location->address_line_1,
                'address_line_2' => $location->address_line_2,
                'city' => $location->city,
                'state' => $location->state,
                'country' => $location->country,
                'country_code' => $location->country_code,
                'latitude' => $location->latitude,
                'longitude' => $location->longitude,
                'location_type' => $location->location_type,
                'iata_code' => $location->iata_code,
                'phone' => $location->phone,
                'pickup_instructions' => $location->pickup_instructions,
                'dropoff_instructions' => $location->dropoff_instructions,
                'is_active' => $location->is_active,
                'vehicles_count' => $location->vehicles_count,
            ])
            ->values();

        return Inertia::render('Vendor/Locations/Index', [
            'locations' => $locations,
        ]);
    }

    public function store(Request $request)
    {
        $vendorId = $request->user()->id;
        $validated = $this->validatePayload($request);

        $this->ensureNoDuplicateLocation($validated, $vendorId);

        VendorLocation::create([
            ...$validated,
            'vendor_id' => $vendorId,
            'is_active' => true,
        ]);

        return back()->with('success', 'Location created successfully.');
    }

    public function update(Request $request, VendorLocation $vendorLocation)
    {
        abort_unless($vendorLocation->vendor_id === $request->user()->id, 403);

        $validated = $this->validatePayload($request);

        $this->ensureNoDuplicateLocation($validated, $request->user()->id, $vendorLocation->id);

        $vendorLocation->update($validated);

        return back()->with('success', 'Location updated successfully.');
    }

    public function destroy(Request $request, VendorLocation $vendorLocation)
    {
        abort_unless($vendorLocation->vendor_id === $request->user()->id, 403);

        if ($vendorLocation->vehicles()->exists()) {
            return back()->withErrors([
                'delete' => 'This location is still linked to vehicles. Reassign those vehicles first.',
            ]);
        }

        $vendorLocation->delete();

        return back()->with('success', 'Location deleted successfully.');
    }

    private function validatePayload(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:100'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'country_code' => ['required', 'string', 'size:2'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'location_type' => ['required', Rule::in(['airport', 'downtown', 'terminal', 'bus stop', 'railway station', 'industrial'])],
            'iata_code' => ['nullable', 'string', 'size:3'],
            'phone' => ['nullable', 'string', 'max:50'],
            'pickup_instructions' => ['nullable', 'string', 'max:2000'],
            'dropoff_instructions' => ['nullable', 'string', 'max:2000'],
        ]);

        $validated['country_code'] = strtoupper($validated['country_code']);
        $validated['location_type'] = strtolower($validated['location_type']);
        $validated['iata_code'] = isset($validated['iata_code']) && $validated['iata_code'] !== ''
            ? strtoupper($validated['iata_code'])
            : null;

        if ($validated['location_type'] === 'airport' && !$validated['iata_code']) {
            $request->validate([
                'iata_code' => ['required', 'string', 'size:3'],
            ]);
        }

        return $validated;
    }

    private function ensureNoDuplicateLocation(array $validated, int $vendorId, ?int $ignoreId = null): void
    {
        $query = VendorLocation::query()->where('vendor_id', $vendorId);

        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        if ($validated['location_type'] === 'airport' && $validated['iata_code']) {
            $query->where('location_type', 'airport')
                ->where('iata_code', $validated['iata_code']);
        } else {
            $query->whereRaw('LOWER(name) = ?', [mb_strtolower($validated['name'])])
                ->whereRaw('LOWER(city) = ?', [mb_strtolower($validated['city'])])
                ->whereRaw('LOWER(country) = ?', [mb_strtolower($validated['country'])])
                ->where('location_type', $validated['location_type']);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages([
                'name' => 'A matching location already exists. Use the existing one instead of creating a duplicate.',
            ]);
        }
    }
}
