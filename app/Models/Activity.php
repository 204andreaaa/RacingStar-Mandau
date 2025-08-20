<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends Model
{
    protected $fillable = ['team_id','name','description','point','is_active'];

    // opsional, tapi bagus buat konsistensi tipe data
    protected $casts = [
        'team_id'   => 'integer',
        'point'     => 'integer',
        'is_active' => 'boolean',
    ];
}
