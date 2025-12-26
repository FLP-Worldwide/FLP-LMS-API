<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassRoutineDay extends Model
{
    use SoftDeletes;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function routine()
    {
        return $this->belongsTo(ClassRoutine::class);
    }

}
