<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class Student extends Model
{
    use SoftDeletes, BelongsToInstitute;

    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected static function booted()
    {
        static::creating(function ($student) {
            if (empty($student->stuid)) {
                $student->stuid = (string) Str::uuid();
            }
            if(empty($student->admission_no)) {
                $student->admission_no = 'ADM-' . strtoupper(Str::random(5));
            }
        });
    }

    public function course()
    {
        return $this->hasOneThrough(
            Course::class,
            ClassRoom::class,
            'id',            // class_rooms.id
            'standard_id',   // courses.standard_id
            'class',         // students.class
            'id'             // class_rooms.id
        );
    }

    public function batch()
    {
        return $this->hasOneThrough(
            Batch::class,
            Course::class,
            'standard_id',   // courses.standard_id
            'course_id',     // batches.course_id
            'class',         // students.class
            'id'             // courses.id
        );
    }

    public function details()
    {
        return $this->hasOne(StudentDetail::class);
    }


    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class', 'id');
    }


    public function fees()
    {
        return $this->hasMany(StudentFee::class);
    }

    public function feeLedgers()
    {
        return $this->hasMany(StudentFeeLedger::class);
    }

    public function concessions()
    {
        return $this->belongsToMany(
            FeeConcession::class,
            'student_fee_concessions'
        );
    }
    public function feeConcessions()
    {
        return $this->hasMany(StudentFeeConcession::class);
    }

    public function examAttendances()
    {
        return $this->hasMany(ExamAttendance::class);
    }

    public function batches()
    {
        return $this->belongsToMany(Batch::class, 'batch_students')
            ->withPivot('assigned_date','is_active')
            ->withTimestamps();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }


}
