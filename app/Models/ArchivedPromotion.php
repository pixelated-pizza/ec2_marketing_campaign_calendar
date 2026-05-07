<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ArchivedPromotion extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'archived_promotions';

    protected $primaryKey = 'archived_promo_id';
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $fillable = [
        'promo_id',
    ];


    /**
     * Promo relationship (UUID FK)
     */
    public function promos()
    {
        return $this->belongsTo(WebsitePromoDetails::class, 'promo_id', 'promo_id');
    }
}
