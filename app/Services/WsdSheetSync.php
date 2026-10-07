<?php

namespace App\Services;

use Revolution\Google\Sheets\Facades\Sheets;
use App\Models\WebsiteSaleDetails;

class WsdSheetSync
{
    protected string $spreadsheetId;
    protected string $sheetName = 'Sheet1';

    public function __construct()
    {
        $this->spreadsheetId = config('services.google.wsd_spreadsheet_id');
    }

    public function pullFromSheet(): array
    {
        $rows = Sheets::spreadsheet($this->spreadsheetId)
            ->sheet($this->sheetName)
            ->all();

        $synced = 0;
        foreach ($rows as $row) {
            if (empty($row['wsd_id'])) {
                continue;
            }

            WebsiteSaleDetails::updateOrCreate(
                ['wsd_id' => $row['wsd_id']],
                [
                    'event_name'   => $row['event_name'] ?? null,
                    'channel_name' => $row['channel_name'] ?? null,
                    'start_date'   => $this->parseDate($row['start_date'] ?? null),
                    'end_date'     => $this->parseDate($row['end_date'] ?? null),
                ]
            );
            $synced++;
        }

        return ['synced' => $synced];
    }

    public function pushToSheet(): array
    {
        $campaigns = WebsiteSaleDetails::orderByDesc('start_date')->get();

        $header = ['wsd_id', 'event_name', 'channel_name', 'start_date', 'end_date'];
        $rows = $campaigns->map(fn ($c) => [
            $c->wsd_id, $c->event_name, $c->channel_name, $c->start_date, $c->end_date,
        ])->toArray();

        Sheets::spreadsheet($this->spreadsheetId)
            ->sheet($this->sheetName)
            ->clear()
            ->update(array_merge([$header], $rows));

        return ['pushed' => count($rows)];
    }

    protected function parseDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }
        try {
            return \Carbon\Carbon::parse($value)->toDateTimeString();
        } catch (\Throwable $e) {
            return null;
        }
    }
}