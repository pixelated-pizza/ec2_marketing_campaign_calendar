<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WeeklyCalendarLayout extends Model
{
    protected $table = 'weekly_calendar_layouts';

    protected $fillable = ['layout_key', 'workbook_data'];

    protected $casts = [
        'workbook_data' => 'array',
    ];
}
