<?php

namespace App\Services\Affiliate;

use App\Models\Affiliate\AffiliateBusiness;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

class AffiliateDeletionService
{
    public function deleteBusiness(AffiliateBusiness $business, bool $deleteLinkedUser = true): void
    {
        $business->loadMissing([
            'businessModel',
            'locations',
            'qrCodes',
            'childBusinesses:id,parent_business_id',
            'user:id,role',
        ]);

        if ($business->childBusinesses->isNotEmpty()) {
            throw new DomainException('Delete child affiliates first before deleting this affiliate.');
        }

        DB::transaction(function () use ($business, $deleteLinkedUser) {
            $linkedUser = $deleteLinkedUser ? $business->user : null;

            foreach ($business->qrCodes as $qrCode) {
                $qrCode->delete();
            }

            foreach ($business->locations as $location) {
                $location->delete();
            }

            if ($business->businessModel) {
                $business->businessModel->delete();
            }

            $business->delete();

            if ($linkedUser && $linkedUser->role === 'affiliate') {
                $linkedUser->delete();
            }
        });
    }

    public function deleteAffiliateUser(User $user): void
    {
        if ($user->role !== 'affiliate') {
            $user->delete();

            return;
        }

        $business = AffiliateBusiness::withTrashed()
            ->where('user_id', $user->id)
            ->first();

        if (! $business || $business->trashed()) {
            $user->delete();

            return;
        }

        $this->deleteBusiness($business, true);
    }
}
