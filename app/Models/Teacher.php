<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Teacher extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected static function booted()
        {
            static::creating(function ($teacher) {
                $teacher->tuid = (string) Str::uuid();
                $teacher->employee_id = (string) "TE-".rand(1000,9999);
            });
        }

        public function detail()
        {
            return $this->hasOne(TeacherDetail::class);
        }

        // Teacher model
        public function details()
        {
            return $this->hasOne(TeacherDetail::class);
        }


        public function subjects()
        {
            return $this->belongsToMany(
                Subject::class,
                'teacher_subjects'
            );
        }

       public function attendances()
        {
            return $this->hasMany(UserAttendance::class, 'teacher_id');
        }


        public function classRooms()
        {
            return $this->belongsToMany(
                ClassRoom::class,
                'teacher_class_rooms'
            );
        }

        public function user()
        {
            return $this->belongsTo(User::class, 'user_id');
        }

}
