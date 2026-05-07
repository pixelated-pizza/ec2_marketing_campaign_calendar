<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\WebsiteCampaign;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\ArchivedPromotion;

use App\Models\WebsitePromoDetails;

class WebsitePromoService
{

    public function all(): Collection
    {
        return WebsitePromoDetails::with('store')->where('is_archived', false)->get();
    }

    public function find(string $id): ?WebsitePromoDetails
    {
        return WebsitePromoDetails::with('store')->find($id);
    }

    public function create(array $data): WebsitePromoDetails
    {
        // Prevent duplicate promo/store combination
        $exists = WebsitePromoDetails::where('promo_name', $data['promo_name'])
            ->where('website_store', $data['website_store'])
            ->first();

        if ($exists) {
            return $exists; // skip creating duplicate
        }

        return DB::transaction(function () use ($data) {
            return WebsitePromoDetails::create($data);
        });
    }

    public function update(string $id, array $data): ?WebsitePromoDetails
    {
        return DB::transaction(function () use ($id, $data) {
            $website_promo = WebsitePromoDetails::find($id);
            if (!$website_promo) {
                return null;
            }
            $website_promo->fill($data);
            $website_promo->updated_at = now();
            $website_promo->save();

            return $website_promo;
        });
    }

    public function delete(string $id): bool
    {
        $website_promo = WebsitePromoDetails::find($id);
        if (!$website_promo) {
            return false;
        }

        return $website_promo->delete();
    }

    public function archive(string $id): ?WebsitePromoDetails
    {
        $promo = WebsitePromoDetails::find($id);

        if (!$promo) {
            return null;
        }

        $matchingPromos = WebsitePromoDetails::where('promo_name', $promo->promo_name)
            ->where('start_date', $promo->start_date)
            ->where('end_date', $promo->end_date)
            ->where('coupon_code', $promo->coupon_code)
            ->get();

        foreach ($matchingPromos as $p) {
            $p->is_archived = true;
            $p->save();

            ArchivedPromotion::create([
                'archived_promo_id' => Str::uuid()->toString(),
                'promo_id' => $p->promo_id,
            ]);
        }

        return $promo;
    }
}
