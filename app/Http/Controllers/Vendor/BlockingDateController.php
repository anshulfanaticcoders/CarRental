<?php

namespace App\Http\Controllers\Vendor;

use App\Http\Controllers\Controller;
use App\Models\BlockingDate;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BlockingDateController extends Controller
{
    public function index(Request $request, $locale)
    {
        $vendorId = auth()->id();
        $searchQuery = $request->input('search', '');
        $filterBy = $request->input('filter_by', 'all'); // all, available, blocked, booked, active_blockings
        $sortBy = $request->input('sort_by', 'id'); // id, brand, model, created_at
        $sortOrder = $request->input('sort_order', 'desc');

        // Get paginated vehicles for display
        $vehiclesQuery = Vehicle::where('vendor_id', $vendorId)
            ->with('blockings','bookings', 'images')
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('brand', 'like', '%' . $searchQuery . '%')
                      ->orWhere('model', 'like', '%' . $searchQuery . '%');
                });
            });

        // Apply filters
        if ($filterBy === 'available') {
            $vehiclesQuery->whereDoesntHave('bookings')
                ->whereDoesntHave('blockings', function ($query) {
                    $today = now()->startOfDay();
                    $query->where('blocking_start_date', '<=', now())
                          ->where('blocking_end_date', '>=', now());
                });
        } elseif ($filterBy === 'blocked') {
            $vehiclesQuery->whereHas('blockings', function ($query) {
                $today = now()->startOfDay();
                $query->where('blocking_start_date', '<=', now())
                      ->where('blocking_end_date', '>=', now());
            });
        } elseif ($filterBy === 'booked') {
            $vehiclesQuery->whereHas('bookings');
        } elseif ($filterBy === 'active_blockings') {
            $vehiclesQuery->whereHas('blockings', function ($query) {
                $today = now()->startOfDay();
                $query->where('blocking_start_date', '<=', now())
                      ->where('blocking_end_date', '>=', now());
            })->withCount(['blockings' => function ($query) {
                $today = now()->startOfDay();
                $query->where('blocking_start_date', '<=', now())
                      ->where('blocking_end_date', '>=', now());
            }]);
        }

        // Apply sorting
        if ($sortBy === 'brand') {
            $vehiclesQuery->orderBy('brand', $sortOrder);
        } elseif ($sortBy === 'model') {
            $vehiclesQuery->orderBy('model', $sortOrder);
        } elseif ($sortBy === 'created_at') {
            $vehiclesQuery->orderBy('created_at', $sortOrder);
        } elseif ($sortBy === 'active_blockings_count') {
            $vehiclesQuery->withCount(['blockings' => function ($query) {
                $today = now()->startOfDay();
                $query->where('blocking_start_date', '<=', now())
                      ->where('blocking_end_date', '>=', now());
            }])->orderBy('blockings_count', $sortOrder);
        } else {
            $vehiclesQuery->orderBy('id', $sortOrder);
        }

        $vehicles = $vehiclesQuery->paginate(7);

        // Calculate total statistics across all vehicles (not paginated)
        $allVehicles = Vehicle::where('vendor_id', $vendorId)
            ->with('blockings', 'bookings')
            ->get();

        $totalVehicles = $allVehicles->count();

        $today = now()->startOfDay();
        $activeBlockings = $allVehicles->sum(function ($vehicle) use ($today) {
            return $vehicle->blockings->filter(function ($blocking) use ($today) {
                $startDate = \Carbon\Carbon::parse($blocking->blocking_start_date)->startOfDay();
                $endDate = \Carbon\Carbon::parse($blocking->blocking_end_date)->endOfDay();
                return $today->between($startDate, $endDate);
            })->count();
        });

        $vehiclesWithBookings = $allVehicles->filter(function ($vehicle) {
            return $vehicle->bookings->isNotEmpty();
        })->count();

        $availableVehicles = $allVehicles->filter(function ($vehicle) use ($today) {
            $hasBookings = $vehicle->bookings->isNotEmpty();
            $hasActiveBlockings = $vehicle->blockings->some(function ($blocking) use ($today) {
                $startDate = \Carbon\Carbon::parse($blocking->blocking_start_date)->startOfDay();
                $endDate = \Carbon\Carbon::parse($blocking->blocking_end_date)->endOfDay();
                return $today->between($startDate, $endDate);
            });
            return !$hasBookings && !$hasActiveBlockings;
        })->count();

        return Inertia::render('Vendor/BlockingDates/Index', [
            'vehicles' => $vehicles,
            'statistics' => [
                'totalVehicles' => $totalVehicles,
                'activeBlockings' => $activeBlockings,
                'vehiclesWithBookings' => $vehiclesWithBookings,
                'availableVehicles' => $availableVehicles,
            ],
            'filters' => [
                'search' => $searchQuery,
                'filter_by' => $filterBy,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }
    

    public function store(Request $request, $locale)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'blocking_start_date' => 'required|date',
            'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
        ]);
    
        BlockingDate::create($validated);
    
        return response()->json(['message' => 'Blocking date added successfully.']);
    }
    

    public function update(Request $request, $locale, $id)
    {
        $validated = $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'blocking_start_date' => 'required|date',
            'blocking_end_date' => 'required|date|after_or_equal:blocking_start_date',
        ]);
    
        $blockingDate = BlockingDate::findOrFail($id);
        $blockingDate->update($validated);
    
        return response()->json(['message' => 'Blocking date updated successfully.']);
    }
    

    public function destroy($locale, $id)
    {
        $blockingDate = BlockingDate::findOrFail($id);
        $blockingDate->delete();
    
        return response()->json(['message' => 'Blocking date removed successfully.']);
    }
    
}
