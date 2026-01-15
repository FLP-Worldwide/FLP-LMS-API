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

    public function details()
    {
        return $this->hasOne(StudentDetail::class);
    }



    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class');
    }

}
