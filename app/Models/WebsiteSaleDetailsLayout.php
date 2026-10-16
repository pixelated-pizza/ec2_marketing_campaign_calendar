<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebsiteSaleDetailsLayout extends Model
{
    protected $table = 'website_sale_details_layouts';

    protected $fillable = ['layout_key', 'workbook_data'];

    protected $casts = [
        'workbook_data' => 'array',
    ];
}
