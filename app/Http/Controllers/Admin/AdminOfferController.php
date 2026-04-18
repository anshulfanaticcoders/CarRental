<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageCompressionHelper;
use App\Http\Controllers\Controller;
use App\Models\Offer;
use App\Services\OfferService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AdminOfferController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('AdminDashboardPages/Offers/Index', [
            'offers' => Offer::query()->with('effects')->latest()->paginate(10),
        ]);
    }

    public function store(Request $request, OfferService $offerService): RedirectResponse
    {
        $validated = $this->validateRequest($request);

        $offer = Offer::create($this->buildOfferData($validated, $request, null));
        $this->syncEffects($offer, $validated);
        $offerService->invalidateCache();

        return redirect()->back()->with('success', 'Offer created successfully.');
    }

    public function update(Request $request, Offer $offer, OfferService $offerService): RedirectResponse
    {
        $validated = $this->validateRequest($request, $offer->id);

        $offer->update($this->buildOfferData($validated, $request, $offer));
        $this->syncEffects($offer, $validated);
        $offerService->invalidateCache();

        return redirect()->back()->with('success', 'Offer updated successfully.');
    }

    public function destroy(Offer $offer, OfferService $offerService): RedirectResponse
    {
        if ($offer->image_path) {
            $this->deleteStoredAsset($offer->image_path);
        }

        $offer->delete();
        $offerService->invalidateCache();

        return redirect()->back()->with('success', 'Offer deleted successfully.');
    }

    private function validateRequest(Request $request, ?int $offerId = null): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'button_text' => 'nullable|string|max:255',
            'button_link' => 'nullable|string|max:255',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'is_active' => 'nullable|boolean',
            'is_external' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:1000',
            'placements' => 'required|array|min:1',
            'placements.*' => 'in:homepage,search,checkout,success',
            'discount_percentage' => 'nullable|numeric|min:0|max:50',
            'include_free_esim' => 'nullable|boolean',
            'slug' => 'nullable|string|max:255|unique:offers,slug,'.($offerId ?? 'NULL').',id',
        ]);
    }

    private function buildOfferData(array $validated, Request $request, ?Offer $existing): array
    {
        $imagePath = $existing?->image_path;

        if ($request->hasFile('image')) {
            if ($imagePath) {
                $this->deleteStoredAsset($imagePath);
            }

            $compressedImagePath = ImageCompressionHelper::compressImage(
                $request->file('image'),
                'offers',
                quality: 85,
                maxWidth: 800,
                maxHeight: 1200
            );

            if ($compressedImagePath) {
                $imagePath = Storage::disk('upcloud')->url($compressedImagePath);
            }
        }

        $slug = $validated['slug'] ?? Str::slug($validated['name']);
        if ($slug === '') {
            $slug = Str::slug($validated['title']).'-'.Str::lower(Str::random(6));
        }

        return [
            'name' => $validated['name'],
            'slug' => $slug,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'button_text' => $validated['button_text'] ?? null,
            'button_link' => $validated['button_link'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'image_path' => $imagePath,
            'is_active' => (bool) ($validated['is_active'] ?? false),
            'is_external' => (bool) ($validated['is_external'] ?? false),
            'priority' => (int) ($validated['priority'] ?? 0),
            'placements' => array_values($validated['placements'] ?? []),
        ];
    }

    private function syncEffects(Offer $offer, array $validated): void
    {
        $offer->effects()->delete();

        $sortOrder = 1;

        if ((float) ($validated['discount_percentage'] ?? 0) > 0) {
            $offer->effects()->create([
                'type' => 'price_discount_percentage',
                'config' => [
                    'percentage' => round((float) $validated['discount_percentage'], 2),
                ],
                'sort_order' => $sortOrder++,
            ]);
        }

        if ((bool) ($validated['include_free_esim'] ?? false)) {
            $offer->effects()->create([
                'type' => 'free_esim',
                'config' => [
                    'included' => true,
                ],
                'sort_order' => $sortOrder++,
            ]);
        }
    }

    private function deleteStoredAsset(string $url): void
    {
        try {
            $parsedPath = parse_url($url, PHP_URL_PATH);
            $relativePath = ltrim((string) $parsedPath, '/');
            if ($relativePath !== '') {
                Storage::disk('upcloud')->delete($relativePath);
            }
        } catch (\Throwable) {
        }
    }
}
