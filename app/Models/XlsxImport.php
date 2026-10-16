<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XlsxImport extends Model
{
    protected $fillable = [
        'name',
        'original_filename',
        'sheet_name',
        'headers',
        'row_count',
    ];

    protected $casts = [
        'headers' => 'array',
        'row_count' => 'integer',
    ];

    public function rows()
    {
        return $this->hasMany(XlsxImportRow::class)->orderBy('row_index')->orderBy('id');
    }
}
