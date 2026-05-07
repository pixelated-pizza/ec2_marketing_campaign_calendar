<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class CategoryFeaturedSkus extends Model
{
    use HasUuids;
    public $timestamps = true;

    protected $table="category_featured_skuses";
    protected $primaryKey = 'sku_featured_id';
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'sku',
        'category_name',
        'block_id',
        'block_name',
        'identifier',
        'type',
        'website',
        'product_landing_page',
        'qty',
        'rrp',
        'website_price',
        'special_price',
        'landing_page',
        'creative_location',
        'price_change',
        'note'
    ];
}
