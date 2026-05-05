<?php

namespace App\Helpers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ActivityLogHelper
{
    /**
     * Map of model FQCN -> category for tab classification.
     */
    private const MODEL_CATEGORY_MAP = [
        'App\\Models\\User' => 'user',
        'App\\Models\\UserProfile' => 'user',
        'App\\Models\\UserDocument' => 'user',
        'App\\Models\\VendorProfile' => 'vendor',
        'App\\Models\\VendorDocument' => 'vendor',
        'App\\Models\\VendorLocation' => 'vendor',
        'App\\Models\\Vehicle' => 'vehicle',
        'App\\Models\\VehicleImage' => 'vehicle',
        'App\\Models\\VehicleCategory' => 'content',
        'App\\Models\\VehicleFeature' => 'content',
        'App\\Models\\Booking' => 'booking',
        'App\\Models\\BookingExtra' => 'booking',
        'App\\Models\\BookingAmount' => 'booking',
        'App\\Models\\BookingPayment' => 'payment',
        'App\\Models\\Affiliate\\AffiliateBusiness' => 'affiliate',
        'App\\Models\\Affiliate\\AffiliateCommission' => 'affiliate',
        'App\\Models\\Affiliate\\AffiliatePayout' => 'affiliate',
        'App\\Models\\Affiliate\\AffiliateBusinessLocation' => 'affiliate',
        'App\\Models\\Affiliate\\AffiliateQrCode' => 'affiliate',
        'App\\Models\\Blog' => 'content',
        'App\\Models\\BlogTranslation' => 'content',
        'App\\Models\\Faq' => 'content',
        'App\\Models\\Testimonial' => 'content',
        'App\\Models\\PopularPlace' => 'content',
        'App\\Models\\Page' => 'content',
        'App\\Models\\Plan' => 'content',
        'App\\Models\\Offer' => 'content',
        'App\\Models\\NewsletterCampaign' => 'content',
        'App\\Models\\NewsletterSubscription' => 'content',
    ];

    /**
     * New canonical signature.
     */
    public static function log(
        string $category,
        string $activityType,
        string $activityDescription,
        $logable = null,
        array $properties = [],
        ?Request $request = null
    ): void {
        $request = $request ?? request();

        ActivityLog::create([
            'user_id' => auth()->id(),
            'category' => $category,
            'activity_type' => $activityType,
            'activity_description' => $activityDescription,
            'properties' => $properties ?: null,
            'logable_id' => $logable?->getKey(),
            'logable_type' => $logable ? get_class($logable) : null,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->header('User-Agent'),
        ]);
    }

    /**
     * Legacy signature — kept for existing call sites.
     * Derives category from $logable class.
     */
    public static function logActivity($activityType, $activityDescription, $logable, Request $request = null)
    {
        self::log(
            self::categoryFor($logable),
            $activityType,
            $activityDescription,
            $logable,
            [],
            $request
        );
    }

    /**
     * Resolve category from a model instance or class string.
     */
    public static function categoryFor($logable): string
    {
        if (!$logable) {
            return 'system';
        }

        $class = is_object($logable) ? get_class($logable) : (string) $logable;

        if (isset(self::MODEL_CATEGORY_MAP[$class])) {
            return self::MODEL_CATEGORY_MAP[$class];
        }

        // Fallback: derive from short class name.
        $short = Str::snake(class_basename($class));
        if (Str::contains($short, ['booking'])) return 'booking';
        if (Str::contains($short, ['payment', 'invoice', 'refund'])) return 'payment';
        if (Str::contains($short, ['vehicle'])) return 'vehicle';
        if (Str::contains($short, ['vendor'])) return 'vendor';
        if (Str::contains($short, ['affiliate'])) return 'affiliate';
        if (Str::contains($short, ['user', 'profile'])) return 'user';

        return 'content';
    }
}
