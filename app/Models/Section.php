<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    public $timeStamps = false;
    public $primaryKey = 'section_id';
    public $incrementing = false;

    protected $fillable = ['name'];

    
}
