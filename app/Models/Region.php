<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    protected $primaryKey = 'id_region';

    protected $fillable = ['nama_region'];

    public function serpos()
    {
        // hasMany ke Serpo: localKey=id_region, foreignKey=id_region
        return $this->hasMany(Serpo::class, 'id_region', 'id_region');
    }
}
