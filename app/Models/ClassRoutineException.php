<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassRoutineException extends Model
{
    use SoftDeletes, BelongsToInstitute;
    protected $table = 'class_routine_exceptions';

    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $casts = [
        'exception_date' => 'date',
        'new_date' => 'date',
        'new_start_time' => 'datetime:H:i',
        'new_end_time' => 'datetime:H:i',
    ];

    public function routine()
    {
        return $this->belongsTo(ClassRoutine::class, 'class_routine_id');
    }
}
