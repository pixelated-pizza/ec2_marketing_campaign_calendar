<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WebsitePromoDetails extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'website_promo_details';

    protected $primaryKey = 'promo_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'promo_id',
        'wc_id',
        'promo_name',
        'description',
        'does_include_parts',
        'does_include_marketplace_products',
        'terms_and_conditions',
        'creatives',
        'coupon_label',
        'coupon_code',
        'website_store',
        'start_date',
        'end_date',
        'updated_at', // custom field
    ];

    protected $casts = [
        'promo_id' => 'string',
        'website_store' => 'string',
        'does_include_parts' => 'boolean',
        'does_include_marketplace_products' => 'boolean',
        'start_date' => 'datetime:Y-m-d H:i:s',
        'end_date' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Store relationship (UUID FK)
     */
    public function store()
    {
        return $this->belongsTo(Store::class, 'website_store', 'store_id');
    }
}
