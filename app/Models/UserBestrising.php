<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class UserBestrising extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'user_bestrising';
    protected $primaryKey = 'id_userBestrising';

    protected $fillable = [
        'nik',
        'nama',
        'email',
        'password',
        'kategori_user_id',
        'id_region',
        'id_serpo',
        // ❌ tidak ada id_segmen di fillable lagi
    ];

    protected $hidden = ['password'];

    public function kategoriUser()
    {
        return $this->belongsTo(KategoriUser::class, 'kategori_user_id', 'id_kategoriuser');
    }

    public function region()
    {
        return $this->belongsTo(Region::class, 'id_region', 'id_region');
    }

    public function serpo()
    {
        return $this->belongsTo(Serpo::class, 'id_serpo', 'id_serpo');
    }
    
    public function segmens()
    {
        // eksplisitkan pivot table + key karena nama tabel & PK custom
        return $this->belongsToMany(
            Segmen::class,
            'segmen_user_bestrising',
            'id_userBestrising',
            'id_segmen'
        )->withTimestamps();
    }

    public function isAdmin(): bool
    {
        $nama = strtolower($this->kategoriUser->nama_kategoriuser ?? '');
        return in_array($nama, ['admin','superadmin'], true);
    }
}
