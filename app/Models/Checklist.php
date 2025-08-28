<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Checklist extends Model
{
    protected $fillable = [
        'user_id','team','id_region','id_serpo','id_segmen',
        'started_at','submitted_at','status','total_point'
    ];

    protected $casts = [
        'started_at'   => 'datetime',
        'submitted_at' => 'datetime',
    ];

    /** Hasil aktivitas detail */
    public function items()
    {
        return $this->hasMany(ActivityResult::class);
    }

    /** Pemilik sesi */
    public function user()
    {
        return $this->belongsTo(UserBestrising::class, 'user_id', 'id_userBestrising');
    }

    /** Lokasi langsung dari kolom foreign key di checklists */
    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region', 'id_region');
    }

    public function serpo()
    {
        return $this->belongsTo(Serpo::class, 'id_serpo', 'id_serpo');
    }

    public function segmen()
    {
        return $this->belongsTo(Segmen::class, 'id_segmen', 'id_segmen'); // single segmen
    }
}
