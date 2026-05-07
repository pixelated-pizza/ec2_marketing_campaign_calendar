<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteCampaign;

use App\Models\ArchivedPromotion;
use App\Models\WebsitePromoDetails;
use Carbon\Carbon;


class ArchivedPromotionService
{

    public function all(): Collection
    {
        return ArchivedPromotion::with('promos.store')->get()->map(function ($archived) {
            $promo = $archived->promos;
            $store = $promo?->store;

            $status = null;

            if ($promo?->start_date && $promo?->end_date) {
                $now = Carbon::now();

                if ($now->between($promo->start_date, $promo->end_date)) {
                    $status = 'ACTIVE';
                } elseif ($now->lt($promo->start_date)) {
                    $status = 'UP NEXT';
                } else {
                    $status = 'ENDED';
                }
            }

            return [
                'promo_id' => $promo?->promo_id,
                'promo_name' => $promo?->promo_name,
                'description' => $promo?->description,
                'terms_and_conditions' => $promo?->terms_and_conditions,
                'does_include_parts' => $promo?->does_include_parts,
                'does_include_marketplace_products' => $promo?->does_include_marketplace_products,
                'creatives' => $promo?->creatives,
                'coupon_label' => $promo?->coupon_label,
                'coupon_code' => $promo?->coupon_code,
                'website_store' => $store?->store_name,
                'store_id' => $store?->store_id,
                'start_date' => $promo?->start_date,
                'end_date' => $promo?->end_date,
                'status' => $status,
                'updated_at' => $promo?->updated_at,
            ];
        });
    }

    public function find(string $id): ?ArchivedPromotion
    {
        return ArchivedPromotion::with('store')->find($id);
    }

    public function create(array $data): ArchivedPromotion
    {
        return DB::transaction(function () use ($data) {
            $archived_promotion = ArchivedPromotion::create($data);
            return $archived_promotion;
        });
    }
    public function update(string $id, array $data): ?ArchivedPromotion
    {
        return DB::transaction(function () use ($id, $data) {
            $archived_promotion = ArchivedPromotion::find($id);
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
        $archived_promotion = ArchivedPromotion::find($id);
        if (!$archived_promotion) {
            return false;
        }

        return $archived_promotion->delete();
    }

    public function unarchive(string $id): ?ArchivedPromotion
    {
        $archived_promotion = ArchivedPromotion::where('promo_id', $id)->first();
        if (!$archived_promotion) {
            return null;
        }

        $promo = WebsitePromoDetails::where('promo_id', $archived_promotion->promo_id)->first();

        if ($promo) {
            $promo->is_archived = false;
            $promo->save();
        }

        $archived_promotion->delete();

        return $archived_promotion;
    }
}
