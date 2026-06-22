<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WSDImportService
{
    public function __construct(protected WSDService $wsdService)
    {
    }

    public function preview(array $rows): array
    {
        return collect($rows)->map(function ($row, $index) {
            $eventName = trim($row['name'] ?? '');
            $channelName = trim($row['store_name'] ?? '');
            $startDate = $this->parseDate($row['start_date'] ?? null);
            $endDate = $this->parseDate($row['end_date'] ?? null);

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

        foreach ($rows as $row) {
            try {
                if (empty($row['event_name']) || empty($row['channel_name'])) {
                    $skipped++;
                    continue;
                }

                $fields = $this->transformFields($row['fields'] ?? []);
                $payload = array_merge($fields, [
                    'event_name' => $row['event_name'],
                    'channel_name' => $row['channel_name'],
                    'start_date' => $row['start_date'] ?? null,
                    'end_date' => $row['end_date'] ?? null,
                ]);

                $this->wsdService->upsert($payload);
                $saved++;
            } catch (\Throwable $e) {
                Log::error('WSD import row failed', ['row' => $row, 'error' => $e->getMessage()]);
                $errors[] = ['row' => $row['event_name'] ?? $row['row_index'] ?? null, 'error' => $e->getMessage()];
            }
        }

        return [
            'details_saved' => $saved,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Convert Yes/No-style CSV text into 1/0, and drop blank cells so
     * DB/service defaults apply instead of overwriting NOT NULL columns.
     */
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

    protected function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        } catch (\Throwable $e) {
            return null;
        }
    }
}