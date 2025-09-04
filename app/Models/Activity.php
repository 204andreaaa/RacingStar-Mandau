<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'description',
        'point',
        'is_active',
        'is_checked_segmen',
        'limit_period', // ⟵ TAMBAH
        'limit_quota',  // ⟵ TAMBAH
        'requires_photo',
    ];

    protected $casts = [
        'team_id'      => 'integer',
        'point'        => 'integer',
        'is_active'    => 'boolean',
        'is_checked_segmen'    => 'boolean',
        'limit_quota'  => 'integer',
        'requires_photo'     => 'boolean',
    ];
}
