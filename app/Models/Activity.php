<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    public $table = 'activities';
    public $primaryKey = 'id';
    public $guarded = [];

    protected $casts = [
        'is_active'         => 'boolean',
        'is_checked_segmen' => 'boolean',
        'requires_photo'    => 'boolean',
        'sub_activities'    => 'array',
        'sort_order'        => 'integer',
    ];

    // Biar gampang dipakai: Activity::ordered()->get()
    public function scopeOrdered($q)
    {
        return $q->orderBy('sort_order')->orderBy('id');
    }
}
