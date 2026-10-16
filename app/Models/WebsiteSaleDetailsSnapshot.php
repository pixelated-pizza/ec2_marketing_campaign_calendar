<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSaleDetailsSnapshot extends Model
{
    protected $table = 'website_sale_details_snapshots';

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
