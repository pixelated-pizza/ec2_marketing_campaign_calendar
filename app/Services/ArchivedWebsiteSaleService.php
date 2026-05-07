<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

use App\Models\ArchivedWebsiteSale;
use App\Models\WebsiteCampaign;

class ArchivedWebsiteSaleService
{
    public function all(): Collection
    {
        return ArchivedWebsiteSale::with('website_campaign')->get()->map(function ($sale) {
            return [
                'ws_archive_id' => $sale->ws_archive_id,
                'wc_id' => $sale->wc_id,
                'archived_at' => $sale->archived_at,

                'campaign' => [
                    'name' => $sale->website_campaign?->name,
                    'start_date' => $sale->website_campaign?->start_date,
                    'end_date' => $sale->website_campaign?->end_date,

                    'store' => [
                        'store_id' => $sale->website_campaign?->store_id,
                        'store_name' => $sale->website_campaign?->store?->store_name,
                    ]
                ],
            ];
        });
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            return DB::table('archived_website_sales')->insert($data);
        });
    }

    public function find(string $ws_archive_id)
    {
        return DB::table('archived_website_sales')->where('ws_archive_id', $ws_archive_id)->first();
    }

    public function unarchive(string $id): ?ArchivedWebsiteSale
    {
        $archived_website_sale = ArchivedWebsiteSale::find($id);
        if (!$archived_website_sale) {
            return null;
        }

        $website_campaign = WebsiteCampaign::where('wc_id', $archived_website_sale->wc_id)->first();

        if ($website_campaign) {
            $website_campaign->is_archived = false;
            $website_campaign->save();
        }

        $archived_website_sale->delete();

        return $archived_website_sale;
    }
}
