<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class XlsxImportRow extends Model
{
    protected $fillable = ['xlsx_import_id', 'row_index', 'data'];

    protected $casts = [
        'data' => 'array',
        'row_index' => 'integer',
    ];

    public function import()
    {
        return $this->belongsTo(XlsxImport::class, 'xlsx_import_id');
    }
}
