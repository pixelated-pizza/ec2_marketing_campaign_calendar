<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WSDService
{
    public function all()
    {
        return DB::table('website_sale_details')
            ->orderBy('start_date', 'asc')
            ->get()
            ->map(fn ($row) => $this->resolveBannerImage($row));
    }

    public function find(string $wsdId)
    {
        return DB::table('website_sale_details')->where('wsd_id', $wsdId)->first();
    }

    public function findByEvent(string $eventName, string $channelName, ?string $startDate)
    {
        return DB::table('website_sale_details')
            ->where('event_name', $eventName)
            ->where('channel_name', $channelName)
            ->where('start_date', $startDate)
            ->first();
    }

    /**
     * Create or update a WSD record. Upsert is keyed on
     * event_name + channel_name + start_date (treated as "the same event run").
     */
    public function upsert(array $data)
    {
        if (empty($data['event_name']) || empty($data['channel_name'])) {
            throw new \InvalidArgumentException('event_name and channel_name are required.');
        }

        try {
            $existing = $this->findByEvent($data['event_name'], $data['channel_name'], $data['start_date'] ?? null);
            $data = $this->applyDefaults($data);

            if ($existing) {
                DB::table('website_sale_details')->where('wsd_id', $existing->wsd_id)->update($data);
                return $this->find($existing->wsd_id);
            }

            $data['wsd_id'] = Str::uuid()->toString();
            DB::table('website_sale_details')->insert($data);

            return $this->find($data['wsd_id']);
        } catch (\Throwable $e) {
            Log::error('WSD upsert failed', ['error' => $e->getMessage(), 'data' => $data]);
            throw new \RuntimeException('Failed to save Website Sale Details: ' . $e->getMessage());
        }
    }

    public function update(string $wsdId, array $data)
    {
        $data = array_filter($data, fn ($value) => !is_null($value));
        DB::table('website_sale_details')->where('wsd_id', $wsdId)->update($data);

        return $this->find($wsdId);
    }

    public function delete(string $wsdId)
    {
        return DB::table('website_sale_details')->where('wsd_id', $wsdId)->delete();
    }

    public function blankRecord(string $eventName, string $channelName, ?string $startDate = null, ?string $endDate = null)
    {
        return array_merge(
            [
                'wsd_id' => Str::uuid()->toString(),
                'event_name' => $eventName,
                'channel_name' => $channelName,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ],
            $this->defaultFields($endDate)
        );
    }

    // ─── Banner image ──────────────────────────────────────────────────────
    // Now keyed directly by wsd_id — no more campaign lookup needed.

    public function uploadImage(string $wsdId, \Illuminate\Http\UploadedFile $file): string
    {
        $existing = $this->find($wsdId);

        if (!$existing) {
            throw new \RuntimeException("No Website Sale Details record found for {$wsdId}.");
        }

        if ($existing->mockup_banner_img) {
            Storage::disk('public')->delete($existing->mockup_banner_img);
        }

        $path = $file->store("wsd/banners/{$wsdId}", 'public');

        DB::table('website_sale_details')->where('wsd_id', $wsdId)->update(['mockup_banner_img' => $path]);

        return asset('storage/' . $path);
    }

    public function deleteImage(string $wsdId): void
    {
        $existing = $this->find($wsdId);

        if (!$existing || !$existing->mockup_banner_img) {
            return;
        }

        Storage::disk('public')->delete($existing->mockup_banner_img);

        DB::table('website_sale_details')->where('wsd_id', $wsdId)->update(['mockup_banner_img' => null]);
    }

    // ─── Internals ─────────────────────────────────────────────────────────

    protected function applyDefaults(array $data): array
    {
        $defaults = $this->defaultFields($data['end_date'] ?? null);

        foreach ($defaults as $field => $defaultValue) {
            if (!array_key_exists($field, $data) || $data[$field] === null) {
                $data[$field] = $defaultValue;
            }
        }

        unset($data['wsd_id']);
        return $data;
    }

    protected function defaultFields(?string $endDate): array
    {
        $formattedEndDate = $endDate ? Carbon::parse($endDate)->format('jS F Y') : 'TBA';

        return [
            'terms_conditions' => "Offer ends 11.59 PM AEDT {$formattedEndDate}. Cannot be combined with any other offer. Prices may change without notice.",
            'mockup_banner_locations' => '',
            'mockup_banner_img' => null,
            'event_master_sheet_url' => '',
            'run_sheet_url' => '',
            'featured_products_sheet_url' => '',
            'is_sku_list_to_feature' => 0,
            'ess' => '',
            'cms_to_audit' => '',
            'sku_in_category_creative' => '',
            'featured_banner_text' => '',
            'url_text' => '',
        ];
    }

    protected function resolveBannerImage($row)
    {
        if ($row->mockup_banner_img) {
            $row->mockup_banner_img = asset('storage/' . $row->mockup_banner_img);
        }

        return $row;
    }
}