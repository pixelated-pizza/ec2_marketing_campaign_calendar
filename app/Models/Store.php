<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    public $timestamps = false;

    public $primaryKey = 'store_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'store_name'
    ];
}
