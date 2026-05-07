<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTitle extends Model
{
    protected $primaryKey = 'category_title_id';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'category_title',
    ];
}
