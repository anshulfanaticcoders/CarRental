<?php

namespace App\Services\Vehicles\BulkImport;

use App\Models\User;
use App\Models\VehicleCategory;
use App\Models\VendorBulkVehicleImage;
use App\Models\VendorLocation;
use Carbon\Carbon;

class BulkVehicleImportRowValidator
{
    private array $categoryCache = [];

    private array $vendorLocationCache = [];

    private array $featureCache = [];

    private array $bulkImageCache = [];

    public function __construct(
        private readonly BulkVehicleImportTemplateService $templateService,
    ) {
    }

    public function validate(array $canonicalRow, User $user, int $rowNumber): array
    {
        $issues = [];
        $normalized = $this->normalizeRow($canonicalRow);

        $category = $this->resolveCategory($normalized['category'] ?? null);
        if (!$category) {
            $issues[] = $this->issue('category', 'Unknown category. Use an existing category name, slug, or id.');
        }

        $vendorLocation = $this->resolveVendorLocation($user, $normalized['vendor_location_code'] ?? null);
        if (!$vendorLocation) {
            $issues[] = $this->issue('vendor_location_code', 'Unknown vendor location. Use an existing saved location id, code, name, or IATA code from your account.');
        } elseif (!$vendorLocation->is_active) {
            $issues[] = $this->issue('vendor_location_code', 'This vendor location exists but is currently inactive.');
        }

        $this->validateRequiredString($issues, 'brand', $normalized['brand'] ?? null, 50);
        $this->validateRequiredString($issues, 'model', $normalized['model'] ?? null, 50);
        $this->validateRequiredString($issues, 'color', $normalized['color'] ?? null, 30);
        $this->validateRequiredString($issues, 'registration_number', $normalized['registration_number'] ?? null, 50);
        $this->validateRequiredString($issues, 'registration_country', $normalized['registration_country'] ?? null, 50);
        $this->validateRequiredString($issues, 'phone_number', $normalized['phone_number'] ?? null, 50);
        $this->validateOptionalString($issues, 'guidelines', $normalized['guidelines'] ?? null, 50000);
        $this->validateOptionalString($issues, 'terms_policy', $normalized['terms_policy'] ?? null, 50000);
        $this->validateOptionalString($issues, 'rental_policy', $normalized['rental_policy'] ?? null, 100000);

        $this->validateEnum($issues, 'body_style', $normalized['body_style'] ?? null);
        $this->validateEnum($issues, 'transmission', $normalized['transmission'] ?? null);
        $this->validateEnum($issues, 'fuel', $normalized['fuel'] ?? null);
        $this->validateEnum($issues, 'status', $normalized['status'] ?? null);

        if (($normalized['fuel_policy'] ?? '') !== '') {
            $this->validateEnum($issues, 'fuel_policy', $normalized['fuel_policy']);
        }

        $airConditioning = $this->parseBoolean($normalized['air_conditioning'] ?? null);
        if ($airConditioning === null) {
            $issues[] = $this->issue('air_conditioning', 'Use yes/no, true/false, or 1/0.');
        }

        $this->validateInteger($issues, 'seating_capacity', $normalized['seating_capacity'] ?? null, 1);
        $this->validateInteger($issues, 'number_of_doors', $normalized['number_of_doors'] ?? null, 2);
        $this->validateInteger($issues, 'minimum_driver_age', $normalized['minimum_driver_age'] ?? null, 18);
        $this->validateInteger($issues, 'mileage', $normalized['mileage'] ?? null, 0);
        $this->validateOptionalInteger($issues, 'luggage_capacity', $normalized['luggage_capacity'] ?? null, 0);
        $this->validateInteger($issues, 'horsepower', $normalized['horsepower'] ?? null, 0);
        $this->validateOptionalInteger($issues, 'gross_vehicle_mass', $normalized['gross_vehicle_mass'] ?? null, 0);

        $this->validateDecimal($issues, 'price_per_day', $normalized['price_per_day'] ?? null, 0);
        $this->validateDecimal($issues, 'security_deposit', $normalized['security_deposit'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'price_per_week', $normalized['price_per_week'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'weekly_discount', $normalized['weekly_discount'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'price_per_month', $normalized['price_per_month'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'monthly_discount', $normalized['monthly_discount'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'price_per_km', $normalized['price_per_km'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'vehicle_height', $normalized['vehicle_height'] ?? null, 0);
        $this->validateOptionalDecimal($issues, 'dealer_cost', $normalized['dealer_cost'] ?? null, 0);

        if (!$this->isStrictDate($normalized['registration_date'] ?? null)) {
            $issues[] = $this->issue('registration_date', 'Use YYYY-MM-DD format.');
        }

        if (($normalized['co2'] ?? '') === '') {
            $issues[] = $this->issue('co2', 'This field is required.');
        }

        $paymentMethods = $this->parseList($normalized['payment_methods'] ?? null);
        if ($paymentMethods === []) {
            $issues[] = $this->issue('payment_methods', 'Add at least one payment method.');
        } else {
            $invalidPaymentMethods = array_values(array_filter(
                $paymentMethods,
                fn (string $value) => !in_array($value, $this->templateService->enumMap()['payment_methods'] ?? [], true)
            ));

            if ($invalidPaymentMethods !== []) {
                $issues[] = $this->issue(
                    'payment_methods',
                    'Unsupported payment methods: '.implode(', ', $invalidPaymentMethods).'.'
                );
            }
        }

        $pickupTimes = $this->parseList($normalized['pickup_times'] ?? null);
        if (!$this->allTimesAreValid($pickupTimes)) {
            $issues[] = $this->issue('pickup_times', 'Use pipe-separated HH:MM values.');
        }

        $returnTimes = $this->parseList($normalized['return_times'] ?? null);
        if (!$this->allTimesAreValid($returnTimes)) {
            $issues[] = $this->issue('return_times', 'Use pipe-separated HH:MM values.');
        }

        $features = $this->parseList($normalized['features'] ?? null);
        if ($features !== [] && $category) {
            $knownFeatures = $this->categoryFeatureLookup($category->id);
            $invalidFeatures = [];

            foreach ($features as $feature) {
                if (!isset($knownFeatures[$this->normalizeValue($feature)])) {
                    $invalidFeatures[] = $feature;
                }
            }

            if ($invalidFeatures !== []) {
                $issues[] = $this->issue(
                    'features',
                    'Unknown features for this category: '.implode(', ', $invalidFeatures).'.'
                );
            }
        }

        $selectedPlans = $this->parseSelectedPlans($normalized, $issues);
        $customAddons = $this->parseCustomAddons($normalized['custom_addons'] ?? null, $issues);

        $primaryImage = $this->resolveImageReference($user, $normalized['primary_image_ref'] ?? null);
        if (($normalized['primary_image_ref'] ?? '') !== '' && !$primaryImage['valid']) {
            $issues[] = $this->issue('primary_image_ref', $primaryImage['message']);
        }

        $galleryImages = [];
        foreach ($this->parseList($normalized['gallery_image_refs'] ?? null) as $galleryRef) {
            $resolvedGalleryImage = $this->resolveImageReference($user, $galleryRef);
            if (!$resolvedGalleryImage['valid']) {
                $issues[] = $this->issue('gallery_image_refs', $resolvedGalleryImage['message']);
                continue;
            }

            $galleryImages[] = $resolvedGalleryImage['data'];
        }

        return [
            'row_number' => $rowNumber,
            'valid' => $issues === [],
            'issues' => $issues,
            'normalized' => $normalized,
            'resolved' => [
                'category_id' => $category?->id,
                'category_name' => $category?->name,
                'vendor_location_id' => $vendorLocation?->id,
                'vendor_location_name' => $vendorLocation?->name,
                'vendor_location_code' => $vendorLocation?->code,
                'payment_methods' => $paymentMethods,
                'features' => $features,
                'pickup_times' => $pickupTimes,
                'return_times' => $returnTimes,
                'air_conditioning' => $airConditioning,
                'selected_plans' => $selectedPlans,
                'custom_addons' => $customAddons,
                'primary_image' => $primaryImage['data'] ?? null,
                'gallery_images' => $galleryImages,
            ],
        ];
    }

    private function normalizeRow(array $canonicalRow): array
    {
        $normalized = [];

        foreach ($this->templateService->templateHeaders() as $column) {
            $normalized[$column] = trim((string) ($canonicalRow[$column] ?? ''));
        }

        foreach (['body_style', 'transmission', 'fuel', 'status', 'fuel_policy'] as $field) {
            if (($normalized[$field] ?? '') !== '') {
                $normalized[$field] = $this->normalizeValue($normalized[$field]);
            }
        }

        return $normalized;
    }

    private function resolveCategory(?string $value): ?VehicleCategory
    {
        $normalizedValue = $this->normalizeValue($value);
        if ($normalizedValue === '') {
            return null;
        }

        if ($this->categoryCache === []) {
            $categories = VehicleCategory::query()
                ->with(['features:id,category_id,feature_name'])
                ->get(['id', 'name', 'slug']);

            foreach ($categories as $category) {
                foreach ([$category->id, $category->name, $category->slug] as $key) {
                    $lookupKey = $this->normalizeValue((string) $key);
                    if ($lookupKey !== '') {
                        $this->categoryCache[$lookupKey] = $category;
                    }
                }

                $this->featureCache[$category->id] = [];
                foreach ($category->features as $feature) {
                    $featureKey = $this->normalizeValue($feature->feature_name);
                    if ($featureKey !== '') {
                        $this->featureCache[$category->id][$featureKey] = $feature->feature_name;
                    }
                }
            }
        }

        return $this->categoryCache[$normalizedValue] ?? null;
    }

    private function resolveVendorLocation(User $user, ?string $value): ?VendorLocation
    {
        $rawValue = trim((string) $value);
        if ($rawValue === '') {
            return null;
        }

        if (!isset($this->vendorLocationCache[$user->id])) {
            $this->vendorLocationCache[$user->id] = [];
            $locations = VendorLocation::query()
                ->where('vendor_id', $user->id)
                ->get(['id', 'vendor_id', 'name', 'code', 'iata_code', 'is_active']);

            foreach ($locations as $location) {
                foreach ([
                    (string) $location->id,
                    trim((string) $location->code),
                    trim((string) $location->name),
                    trim((string) $location->iata_code),
                ] as $lookupValue) {
                    $normalizedLookupValue = $this->normalizeValue($lookupValue);
                    if ($normalizedLookupValue !== '') {
                        $this->vendorLocationCache[$user->id][$normalizedLookupValue] = $location;
                    }
                }
            }
        }

        return $this->vendorLocationCache[$user->id][$this->normalizeValue($rawValue)] ?? null;
    }

    private function categoryFeatureLookup(int $categoryId): array
    {
        return $this->featureCache[$categoryId] ?? [];
    }

    private function resolveImageReference(User $user, ?string $value): array
    {
        $reference = trim((string) $value);
        if ($reference === '') {
            return ['valid' => true, 'data' => null];
        }

        if (filter_var($reference, FILTER_VALIDATE_URL)) {
            return [
                'valid' => true,
                'data' => [
                    'type' => 'url',
                    'value' => $reference,
                ],
            ];
        }

        if (preg_match('/^bulkimg_(\d+)$/i', $reference, $matches) !== 1) {
            return [
                'valid' => false,
                'message' => 'Use a valid image URL or a bulk image token like bulkimg_123.',
            ];
        }

        $imageId = (int) $matches[1];
        $image = $this->resolveBulkImage($user, $imageId);
        if (!$image) {
            return [
                'valid' => false,
                'message' => 'Bulk image token '.$reference.' does not belong to your uploaded image library.',
            ];
        }

        return [
            'valid' => true,
            'data' => [
                'type' => 'bulk_image',
                'id' => $image->id,
                'path' => $image->image_path,
                'thumbnail_path' => $image->thumbnail_path,
                'original_name' => $image->original_name,
            ],
        ];
    }

    private function resolveBulkImage(User $user, int $imageId): ?VendorBulkVehicleImage
    {
        if (!isset($this->bulkImageCache[$user->id])) {
            $this->bulkImageCache[$user->id] = VendorBulkVehicleImage::query()
                ->where('user_id', $user->id)
                ->get(['id', 'user_id', 'image_path', 'thumbnail_path', 'original_name'])
                ->keyBy('id')
                ->all();
        }

        return $this->bulkImageCache[$user->id][$imageId] ?? null;
    }

    private function validateRequiredString(array &$issues, string $field, ?string $value, int $maxLength): void
    {
        if (trim((string) $value) === '') {
            $issues[] = $this->issue($field, 'This field is required.');
            return;
        }

        if (mb_strlen((string) $value) > $maxLength) {
            $issues[] = $this->issue($field, 'This value is too long.');
        }
    }

    private function validateOptionalString(array &$issues, string $field, ?string $value, int $maxLength): void
    {
        if (trim((string) $value) === '') {
            return;
        }

        if (mb_strlen((string) $value) > $maxLength) {
            $issues[] = $this->issue($field, 'This value is too long.');
        }
    }

    private function validateEnum(array &$issues, string $field, ?string $value): void
    {
        $allowed = $this->templateService->enumMap()[$field] ?? [];
        if ($value === null || $value === '') {
            $issues[] = $this->issue($field, 'This field is required.');
            return;
        }

        if (!in_array($value, $allowed, true)) {
            $issues[] = $this->issue($field, 'Accepted values: '.implode(', ', $allowed).'.');
        }
    }

    private function validateInteger(array &$issues, string $field, ?string $value, int $minimum): void
    {
        if ($value === null || $value === '') {
            $issues[] = $this->issue($field, 'This field is required.');
            return;
        }

        if (!preg_match('/^-?\d+$/', $value) || (int) $value < $minimum) {
            $issues[] = $this->issue($field, 'Use an integer value greater than or equal to '.$minimum.'.');
        }
    }

    private function validateOptionalInteger(array &$issues, string $field, ?string $value, int $minimum): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!preg_match('/^-?\d+$/', $value) || (int) $value < $minimum) {
            $issues[] = $this->issue($field, 'Use an integer value greater than or equal to '.$minimum.'.');
        }
    }

    private function validateDecimal(array &$issues, string $field, ?string $value, int|float $minimum): void
    {
        if ($value === null || $value === '') {
            $issues[] = $this->issue($field, 'This field is required.');
            return;
        }

        $this->validateOptionalDecimal($issues, $field, $value, $minimum);
    }

    private function validateOptionalDecimal(array &$issues, string $field, ?string $value, int|float $minimum): void
    {
        if ($value === null || $value === '') {
            return;
        }

        if (!is_numeric($value) || (float) $value < $minimum) {
            $issues[] = $this->issue($field, 'Use a numeric value greater than or equal to '.$minimum.'.');
        }
    }

    private function isStrictDate(?string $value): bool
    {
        if ($value === null || trim($value) === '') {
            return false;
        }

        try {
            return Carbon::createFromFormat('Y-m-d', $value)->format('Y-m-d') === $value;
        } catch (\Throwable) {
            return false;
        }
    }

    private function parseBoolean(?string $value): ?bool
    {
        $normalizedValue = $this->normalizeValue($value);
        if ($normalizedValue === '') {
            return null;
        }

        return match ($normalizedValue) {
            '1', 'true', 'yes', 'y' => true,
            '0', 'false', 'no', 'n' => false,
            default => null,
        };
    }

    private function parseList(?string $value): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[|,]/', $raw) ?: [];

        return array_values(array_filter(array_map(
            fn (string $item) => trim($item),
            $parts
        ), static fn (string $item) => $item !== ''));
    }

    private function allTimesAreValid(array $values): bool
    {
        foreach ($values as $value) {
            if (!preg_match('/^(2[0-3]|[01]\d):[0-5]\d$/', $value)) {
                return false;
            }
        }

        return true;
    }

    private function parseSelectedPlans(array $normalized, array &$issues): array
    {
        $dailyPrice = is_numeric($normalized['price_per_day'] ?? null)
            ? (float) $normalized['price_per_day']
            : 0.0;

        $plans = [];

        foreach ([
            'Essential' => ['price' => 'essential_plan_price', 'features' => 'essential_plan_features', 'description' => 'essential_plan_description'],
            'Premium' => ['price' => 'premium_plan_price', 'features' => 'premium_plan_features', 'description' => 'premium_plan_description'],
            'Premium Plus' => ['price' => 'premium_plus_plan_price', 'features' => 'premium_plus_plan_features', 'description' => 'premium_plus_plan_description'],
        ] as $planType => $fields) {
            $priceValue = trim((string) ($normalized[$fields['price']] ?? ''));
            $featureValues = $this->parseList($normalized[$fields['features']] ?? null);
            $descriptionValue = trim((string) ($normalized[$fields['description']] ?? ''));

            if ($priceValue === '' && $featureValues === [] && $descriptionValue === '') {
                continue;
            }

            if ($priceValue === '') {
                $issues[] = $this->issue($fields['price'], 'Plan price is required when any plan data is provided.');
                continue;
            }

            if (!is_numeric($priceValue) || (float) $priceValue < 0) {
                $issues[] = $this->issue($fields['price'], 'Use a numeric plan price greater than or equal to 0.');
                continue;
            }

            if ((float) $priceValue < $dailyPrice) {
                $issues[] = $this->issue($fields['price'], 'Plan price must be at least the daily price.');
            }

            if (count($featureValues) > 5) {
                $issues[] = $this->issue($fields['features'], 'Use up to 5 plan features only.');
            }

            if (mb_strlen($descriptionValue) > 2000) {
                $issues[] = $this->issue($fields['description'], 'Plan description is too long.');
            }

            $plans[] = [
                'plan_type' => $planType,
                'plan_value' => (float) $priceValue,
                'plan_description' => $descriptionValue !== '' ? $descriptionValue : null,
                'features' => array_slice($featureValues, 0, 5),
            ];
        }

        return $plans;
    }

    private function parseCustomAddons(?string $value, array &$issues): array
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $addons = [];
        $entries = preg_split('/\s*;\s*/', $raw) ?: [];

        foreach ($entries as $index => $entry) {
            $entry = trim($entry);
            if ($entry === '') {
                continue;
            }

            $parts = array_map('trim', explode('~', $entry));
            $name = $parts[0] ?? '';
            $price = $parts[1] ?? '';
            $quantity = $parts[2] ?? '1';
            $type = $parts[3] ?? 'custom';
            $description = implode('~', array_slice($parts, 4));

            if ($name === '') {
                $issues[] = $this->issue('custom_addons', 'Each addon must start with a name. Use name~price~quantity~type~description.');
                continue;
            }

            if (!is_numeric($price) || (float) $price < 0) {
                $issues[] = $this->issue('custom_addons', 'Addon "'.$name.'" must have a numeric price greater than or equal to 0.');
                continue;
            }

            if (!preg_match('/^\d+$/', $quantity) || (int) $quantity < 1) {
                $issues[] = $this->issue('custom_addons', 'Addon "'.$name.'" must have a quantity of at least 1.');
                continue;
            }

            if (mb_strlen($name) > 255) {
                $issues[] = $this->issue('custom_addons', 'Addon "'.$name.'" name is too long.');
                continue;
            }

            if (mb_strlen($type) > 255) {
                $issues[] = $this->issue('custom_addons', 'Addon "'.$name.'" type is too long.');
                continue;
            }

            if (mb_strlen($description) > 2000) {
                $issues[] = $this->issue('custom_addons', 'Addon "'.$name.'" description is too long.');
                continue;
            }

            $addons[] = [
                'extra_name' => $name,
                'price' => (float) $price,
                'quantity' => (int) $quantity,
                'extra_type' => $type !== '' ? $type : 'custom',
                'description' => $description !== '' ? $description : '',
            ];
        }

        return $addons;
    }

    private function normalizeValue(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function issue(string $field, string $message): array
    {
        return [
            'field' => $field,
            'message' => $message,
        ];
    }
}
