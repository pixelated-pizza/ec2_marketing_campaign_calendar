<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Carbon\Carbon;

class WSDImportService
{
    public function __construct(protected WSDService $wsdService) {}

    public function preview(array $rows): array
    {
        return collect($rows)->map(function ($row, $index) {
            $eventName = trim($row['name'] ?? '');
            $channelName = trim($row['store_name'] ?? '');
            $startDate = $this->parseDate($row['start_date'] ?? null, 'start');
            $endDate   = $this->parseDate($row['end_date']   ?? null, 'end');

            $valid = $eventName !== '' && $channelName !== '';

            $existing = $valid
                ? DB::table('website_sale_details')
                ->where('event_name', $eventName)
                ->where('channel_name', $channelName)
                ->where('start_date', $startDate)
                ->first()
                : null;

            return [
                'row_index' => $index,
                'event_name' => $eventName,
                'channel_name' => $channelName,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'fields' => $row['fields'] ?? [],
                'valid' => $valid,
                'will_update' => (bool) $existing,
            ];
        })->values()->all();
    }

    public function commit(array $rows): array
    {
        $saved = 0;
        $skipped = 0;
        $errors = [];

        // A pair = same event_name present for both a Mytopia and an Edisons row.
        // Used to set is_applied_to_both_stores = 1 on both website_campaign rows.
        $pairedEventNames = collect($rows)
            ->filter(fn($r) => !empty($r['event_name']) && !empty($r['channel_name']))
            ->groupBy('event_name')
            ->filter(
                fn($group) => $group
                    ->pluck('channel_name')
                    ->map(fn($c) => $this->normaliseChannel($c))
                    ->unique()
                    ->count() > 1
            )
            ->keys()
            ->flip() // ['Event Name' => index] for O(1) isset() lookup
            ->all();

        // Google Sheets merged-cell artifact: dates only export on the first row of
        // a merge. Build a map of event_name → dates from whichever row has them,
        // then backfill any sibling rows that came through with blank dates.
        $datesByEvent = collect($rows)
            ->filter(fn($r) => !empty($r['event_name']) && !empty($r['start_date']))
            ->keyBy('event_name')
            ->map(fn($r) => [
                'start_date' => $this->parseDate($r['start_date'], 'start'),
                'end_date'   => $this->parseDate($r['end_date'] ?? null, 'end'),
            ])
            ->all();

        $resolvedRows = array_map(function ($row) use ($datesByEvent) {
            if (empty($row['start_date']) && !empty($row['event_name'])) {
                $inherited = $datesByEvent[$row['event_name']] ?? null;
                if ($inherited) {
                    $row['start_date'] = $inherited['start_date'];
                    $row['end_date'] = $inherited['end_date'];
                }
            }
            return $row;
        }, $rows);

        //Commit

        foreach ($resolvedRows as $row) {
            try {
                if (empty($row['event_name']) || empty($row['channel_name'])) {
                    $skipped++;
                    continue;
                }

                $isPaired = isset($pairedEventNames[$row['event_name']]);
                $hasDate = !empty($row['start_date']) && !empty($row['end_date']);

                DB::transaction(function () use ($row, $isPaired, $hasDate) {
                    // Always save WSD
                    $fields = $this->transformFields($row['fields'] ?? []);
                    $this->wsdService->upsert(array_merge($fields, [
                        'event_name'   => $row['event_name'],
                        'channel_name' => $row['channel_name'],
                        'start_date'   => $this->parseDate($row['start_date'] ?? null, 'start'),
                        'end_date'     => $this->parseDate($row['end_date']   ?? null, 'end'),
                    ]));

                    // Sync to campaigns + website_campaigns only when dates are available.
                    // campaigns.start_date / end_date are NOT NULL, so we cannot insert without them.
                    if ($hasDate) {
                        $this->syncToCampaigns($row, $isPaired);
                    }
                });

                $saved++;
            } catch (\Throwable $e) {
                Log::error('WSD import row failed', ['row' => $row, 'error' => $e->getMessage()]);
                $errors[] = [
                    'row' => $row['event_name'] ?? $row['row_index'] ?? null,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'details_saved' => $saved,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Insert into campaigns + website_campaigns for a given WSD row.
     * Both inserts are skipped (not errored) if a matching record already exists,
     * so re-running the same import is safe.
     */
    protected function syncToCampaigns(array $row, bool $isPaired): void
    {
        $normalisedChannel = $this->normaliseChannel($row['channel_name']);
        $startDate = $this->parseDate($row['start_date'] ?? null, 'start');
        $endDate   = $this->parseDate($row['end_date']   ?? null, 'end');

        // Lookups

        $store = DB::table('stores')
            ->whereRaw('LOWER(store_name) LIKE ?', ['%' . strtolower($normalisedChannel) . '%'])
            ->first();

        if (!$store) {
            throw new \RuntimeException("No store found matching \"{$row['channel_name']}\".");
        }

        $categoryChannel = DB::table('category_channels')
            ->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($normalisedChannel) . '%'])
            ->first();

        if (!$categoryChannel) {
            throw new \RuntimeException("No category channel found matching \"{$row['channel_name']}\".");
        }

        $campaignType = DB::table('website_campaign_types')
            ->where('campaign_type_name', 'Website Sale')
            ->first();

        if (!$campaignType) {
            throw new \RuntimeException('Website Sale campaign type not found.');
        }

        $section = DB::table('sections')
            ->where('name', 'Homepage Banner')
            ->first();

        if (!$section) {
            throw new \RuntimeException('Homepage Banner section not found.');
        }

        // campaigns 

        $startDateOnly = Carbon::parse($startDate)->toDateString();
        $endDateOnly = Carbon::parse($endDate)->toDateString();

        $existingCampaign = DB::table('campaigns')
            ->where('channel_id', $categoryChannel->channel_id)
            ->where('name', $row['event_name'])
            ->where('start_date', $startDateOnly)
            ->first();

        if (!$existingCampaign) {
            DB::table('campaigns')->insert([
                'campaign_id' => Str::uuid()->toString(),
                'channel_id' => $categoryChannel->channel_id,
                'name' => $row['event_name'],
                'start_date' => $startDateOnly,
                'end_date' => $endDateOnly,
            ]);
        }

        // website_campaigns

        $existingWC = DB::table('website_campaigns')
            ->where('store_id', $store->store_id)
            ->where('name', $row['event_name'])
            ->where('start_date', $startDate)
            ->first();

        if (!$existingWC) {
            DB::table('website_campaigns')->insert([
                'wc_id' => Str::uuid()->toString(),
                'name' => $row['event_name'],
                'campaign_type_id' => $campaignType->campaign_type_id,
                'section_id' => $section->section_id,
                'store_id' => $store->store_id,
                'is_applied_to_both_stores' => $isPaired ? 1 : 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
            ]);
        }
    }

    protected function transformFields(array $fields): array
    {
        if (array_key_exists('is_sku_list_to_feature', $fields)) {
            $value = strtolower(trim((string) $fields['is_sku_list_to_feature']));
            $fields['is_sku_list_to_feature'] = in_array($value, ['yes', 'y', '1', 'true'], true) ? 1 : 0;
        }

        foreach ($fields as $key => $value) {
            if ($value === '' || $value === null) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    protected function normaliseChannel(?string $value): string
    {
        return trim(str_ireplace('website ', '', trim($value ?? '')));
    }

    protected function parseDate(?string $value, string $type = 'start'): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            // Extract only the date portion to avoid any timezone drift on re-parse
            $dateOnly = Carbon::parse($value)->format('Y-m-d');
            $time = $type === 'start' ? '09:00:00' : '23:59:59';

            return "{$dateOnly} {$time}";
        } catch (\Throwable $e) {
            return null;
        }
    }
}
