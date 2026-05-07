<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{
    public $timestamps = false;

    public $primaryKey = 'role_id';
    protected $keyType = 'string';

    protected $fillable = ['role_name']; 
}
