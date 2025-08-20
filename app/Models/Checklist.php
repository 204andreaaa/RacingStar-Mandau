<?php

// app/Models/Checklist.php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Checklist extends Model {
  protected $fillable = [
    'user_id','team','id_region','id_serpo','id_segmen',
    'started_at','submitted_at','status','total_point'
  ];
  public function items(){ return $this->hasMany(ActivityResult::class); }

  public function user()
    {
        return $this->belongsTo(UserBestrising::class, 'user_id', 'id_userBestrising');
    }

}
