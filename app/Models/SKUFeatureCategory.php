<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SKUFeatureCategory extends Model
{
    public $timestamps = false;

    public $primaryKey = 'uuid';
    public $incrementing = false;

    protected $fillable = [
        'wsd_id',
        'category_name',
    ];
}
