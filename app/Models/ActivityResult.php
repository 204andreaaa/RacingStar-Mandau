<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityResult extends Model
{
    protected $fillable = [
        'checklist_id',      // <— TAMBAHKAN INI
        'user_id',
        'activity_id',
        'id_segmen',
        'submitted_at',
        'status',
        'before_photo',
        'after_photo',
        'point_earned',
        'note',
    ];
    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    // app/Models/ActivityResult.php
    public function checklist()
    { 
        return $this->belongsTo(\App\Models\Checklist::class); 
    }

    public function user()
    {
        return $this->belongsTo(UserBestrising::class, 'user_id', 'id_userBestrising');
    }

    public function segmen() {
        return $this->belongsTo(Segmen::class, 'id_segmen');
    }
}
