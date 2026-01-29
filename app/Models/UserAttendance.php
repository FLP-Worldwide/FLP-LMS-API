<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAttendance extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $table = 'teacher_attendances';
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    // 🔹 Teacher (optional)
    public function teacher()
    {
        return $this->belongsTo(Teacher::class);
    }

    // 🔹 User (staff + teacher)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
