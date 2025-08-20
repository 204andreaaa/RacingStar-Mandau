<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Serpo extends Model
{
    use HasFactory;

    protected $table = 'serpos';
    protected $primaryKey = 'id_serpo';

    protected $fillable = ['id_region', 'nama_serpo'];

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region', 'id_region');
    }

    public function segmens()
    {
        return $this->hasMany(Segmen::class, 'id_serpo', 'id_serpo');
    }
}
