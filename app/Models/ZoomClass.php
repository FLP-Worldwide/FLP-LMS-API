<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZoomClass extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $casts = [
        'settings' => 'array',
        'date' => 'date',
    ];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'zoom_class_teacher', 'zoom_class_id', 'teacher_id');
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'zoom_class_course');
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'zoom_class_batch');
    }

    public function students()
    {
        return $this->belongsToMany(Student::class, 'zoom_class_student');
    }
}
