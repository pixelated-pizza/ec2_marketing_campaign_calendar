<?php

namespace App\Services;

use App\Models\WebsiteCampaign;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Models\Campaign;
use App\Models\ArchivedPromotion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\WebsitePromoDetails;
use App\Models\WebsiteSaleDetails;

class WebsiteService
{
    public function all(): Collection
    {
        return WebsiteCampaign::with(['store', 'section'])
            ->where('is_archived', false)
            ->get();
    }

    public function find(string $id): ?WebsiteCampaign
    {
        return WebsiteCampaign::with(['store', 'section'])->find($id);
    }

    public function create(array $data): Collection
    {
        return DB::transaction(function () use ($data) {

            // Determine which stores to apply the campaign to
            $storeIds = [];
            if (!empty($data['is_all_store']) && $data['is_all_store']) {
                $storeIds = DB::table('stores')
                    ->whereIn('store_name', ['Website - Edisons', 'Website - Mytopia'])
                    ->pluck('store_id')
                    ->toArray();
            } else {
                $storeIds = [$data['store_id']];
            }

            $createdCampaigns = collect();

            foreach ($storeIds as $storeId) {
                $website_campaign = WebsiteCampaign::create(array_merge($data, [
                    'store_id' => $storeId
                ]));

                $storeName = DB::table('stores')->where('store_id', $storeId)->value('store_name');
                if ($storeName === 'Website - Mytopia') {
                    $channel_id = DB::table('category_channels')
                        ->where('name', 'Mytopia')
                        ->value('channel_id');
                } else if ($storeName === 'Website - Edisons') {
                    $channel_id = DB::table('category_channels')
                        ->where('name', 'Edisons')
                        ->value('channel_id');
                }

                // Create Campaign entry
                Campaign::create([
                    'channel_id' => $channel_id,
                    'name' => $data['name'],
                    'background_color' => '#16a34a',
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                ]);

                $adhocPromoTypeId = DB::table('website_campaign_types')
                    ->where('campaign_type_name', 'Adhoc Promos/Coupons')
                    ->value('campaign_type_id');

                if ($website_campaign->campaign_type_id === $adhocPromoTypeId) {
                    WebsitePromoDetails::create([
                        'promo_id' => Str::uuid()->toString(),
                        'wc_id' => $website_campaign->wc_id,
                        'promo_name' => $data['name'],
                        'description' => $data['description'] ?? $data['name'],
                        'terms_and_conditions' => $data['terms_and_conditions'] ?? 'Cannot be combined with any other offer. Prices may change without notice.',
                        'does_include_parts' => $data['does_include_parts'] ?? false,
                        'does_include_marketplace_products' => $data['does_include_marketplace_products'] ?? false,
                        'creatives' => $data['creatives'] ?? 'N/A',
                        'coupon_label' => $data['coupon_label'] ?? null,
                        'coupon_code' => $data['coupon_code'] ?? null,
                        'website_store' => $storeId,
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);
                } 

                $createdCampaigns->push($website_campaign);
            }

            return $createdCampaigns;
        });
    }


    public function update(string $id, array $data): ?WebsiteCampaign
    {

        return DB::transaction(function () use ($id, $data) {
            $website_campaign = WebsiteCampaign::find($id);
            if (!$website_campaign) {
                return null;
            }

            $campaign = Campaign::where('name', $website_campaign->name)->first();


            $website_campaign->update($data);

            $website_campaign->load('store');

            if (in_array($website_campaign->store->store_name, ['Website - Edisons', 'Website - Mytopia'])) {

                if ($campaign) {
                    $campaign->update([
                        'name' => $data['name'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                    ]);

                
                }
            }

            return $website_campaign;
        });
    }

    public function delete(string $id): bool
    {
        $website_campaign = WebsiteCampaign::find($id);
        if (!$website_campaign) {
            return false;
            
        }

        return DB::transaction(function () use ($website_campaign) {

            Campaign::where('campaign_id', $website_campaign->website_campaign_key)->delete();

            WebsitePromoDetails::where('wc_id', $website_campaign->wc_id)->delete();

            return (bool) $website_campaign->delete();
        });
    }


    public function archive(string $id): ?WebsiteCampaign
    {
        $campaign = WebsiteCampaign::find($id);
        if (!$campaign) {
            return null;
        }

        $campaign->is_archived = true;
        $campaign->save();

        return $campaign;
    }
}
