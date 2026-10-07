<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SheetMirror extends Model
{
    protected $fillable = [
        'key',
        'spreadsheet_id',
        'sheet_name',
        'headers',
        'last_synced_at',
    ];

    protected $casts = [
        'headers' => 'array',
        'last_synced_at' => 'datetime',
    ];

    public function rows()
    {
        return $this->hasMany(SheetMirrorRow::class);
    }
}
