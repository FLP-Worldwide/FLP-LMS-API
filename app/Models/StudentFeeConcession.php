<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeConcession extends Model
{
    use BelongsToInstitute;

    protected $table = 'student_fee_concessions';

    protected $guarded = ['id'];

    protected $hidden = ['created_at', 'updated_at'];

    /* ================= Relationships ================= */

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function concession()
    {
        return $this->belongsTo(FeeConcession::class, 'fee_concession_id');
    }
}
