<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SheetMirrorRow extends Model
{
    protected $fillable = ['sheet_mirror_id', 'row_index', 'data'];
    protected $casts = ['data' => 'array'];
}
