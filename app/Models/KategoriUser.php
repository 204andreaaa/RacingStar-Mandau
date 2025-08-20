<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriUser extends Model
{
    use HasFactory;

    protected $table = 'kategoriuser';
    protected $primaryKey = 'id_kategoriuser';

    protected $fillable = [
        'nama_kategoriuser',
    ];

    public function users()
    {
        return $this->hasMany(UserBestrising::class, 'kategori_user_id', 'id_kategoriuser');
    }
}