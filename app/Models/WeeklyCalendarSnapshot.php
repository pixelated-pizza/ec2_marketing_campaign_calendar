<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyCalendarSnapshot extends Model
{
    protected $table = 'weekly_calendar_snapshots';

    protected $fillable = [
        'name',
        'source_filename',
        'row_count',
        'column_count',
        'workbook_data',
    ];

    protected $casts = [
        'workbook_data' => 'array',
    ];
}
