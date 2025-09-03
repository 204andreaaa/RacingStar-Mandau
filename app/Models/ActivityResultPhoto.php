<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityResultPhoto extends Model
{
    protected $fillable = ['activity_result_id','kind','path'];

    public function result()
    {
        return $this->belongsTo(ActivityResult::class, 'activity_result_id');
    }
}
