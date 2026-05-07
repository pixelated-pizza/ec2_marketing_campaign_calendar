<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkuFeature extends Model
{
    protected $primaryKey = 'sku_feature_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'sku',
    ];
}
