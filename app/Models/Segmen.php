<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Segmen extends Model
{
    use HasFactory;

    protected $table = 'segmens';
    protected $primaryKey = 'id_segmen';

    protected $fillable = ['id_serpo', 'nama_segmen'];

    public function serpo()
    {
        return $this->belongsTo(Serpo::class, 'id_serpo', 'id_serpo');
    }

    public function region()
    {
        return $this->serpo?->region();
    }

    // --- RELATIONS ---

    public function users()
    {
        return $this->belongsToMany(
            UserBestrising::class,
            'segmen_user_bestrising',
            'id_segmen',
            'id_userBestrising'
        )->withTimestamps();
    }

    public function getRegionAttrAttribute()
    {
        return $this->serpo?->region ?? null;
    }
}
