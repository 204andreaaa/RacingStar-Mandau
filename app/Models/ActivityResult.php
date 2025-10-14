<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActivityResult extends Model
{
    use SoftDeletes;

    public $table = 'activity_results';
    public $primaryKey = 'id';
    public $guarded = [];

    protected $casts = [
        'sub_activities' => 'array',
        'is_approval' => 'boolean',
        'is_rejected' => 'boolean',
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

    public function photos()
    {
        return $this->hasMany(ActivityResultPhoto::class, 'activity_result_id');
    }

    public function beforePhotos()
    {
        return $this->photos()->where('kind','before');
    }

    public function afterPhotos()
    {
        return $this->photos()->where('kind','after');
    }
}
