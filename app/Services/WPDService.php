<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteCampaign;

use App\Models\WebsitePromoDetails;

class WPDService
{

    public function all(): Collection
    {
        return WebsitePromoDetails::with('store')->get()->map(function ($promo) {
            return [
                'promo_id' => $promo->promo_id,
                'promo_name' => $promo->promo_name,
                'description' => $promo->description,
                'terms_and_conditions' => $promo->terms_and_conditions,
                'does_include_parts' => $promo->does_include_parts,
                'does_include_marketplace_products' => $promo->does_include_marketplace_products,
                'creatives' => $promo->creatives,
                'coupon_label' => $promo->coupon_label,
                'coupon_code' => $promo->coupon_code,
                'website_store' => $promo->store ? $promo->store->store_name : null, // map store name here
                'store_id' => $promo->store ? $promo->store->store_id : null,
                'start_date' => $promo->start_date,
                'end_date' => $promo->end_date,
                'status' => $promo->status,
                'updated_at' => $promo->updated_at,
            ];
        });
    }

    public function find(string $id): ?WebsitePromoDetails
    {
        return WebsitePromoDetails::with('store')->find($id);
    }

    public function create(array $data): WebsitePromoDetails
    {
        return DB::transaction(function () use ($data) {
            $archived_promotion = WebsitePromoDetails::create($data);
            return $archived_promotion;
        });
    }
    public function update(string $id, array $data): ?WebsitePromoDetails
    {
        return DB::transaction(function () use ($id, $data) {
            $archived_promotion = WebsitePromoDetails::find($id);
            if (!$archived_promotion) {
                return null;
            }
            $archived_promotion->fill($data); 
            $archived_promotion->updated_at = now();
            $archived_promotion->save();

            return $archived_promotion;
        });
    }

    public function delete(string $id): bool
    {
        $archived_promotion = WebsitePromoDetails::find($id);
        if (!$archived_promotion) {
            return false;
        }

        return $archived_promotion->delete();
    }

    public function unarchive(string $id): ?WebsitePromoDetails
    {
        $archived_promotion = WebsitePromoDetails::find($id);
        if (!$archived_promotion) {
            return null;
        }

        $website_campaign = WebsiteCampaign::where('wc_id', $archived_promotion->wc_id)->first();

        if ($website_campaign) {
            $website_campaign->is_archived = false;
            $website_campaign->save();
        }

        $archived_promotion->delete();

        return $archived_promotion;
    }
}
