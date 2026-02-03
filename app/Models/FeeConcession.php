<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeeConcession extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function batches()
    {
        return $this->belongsToMany(
            Batch::class,
            'fee_concession_batches'
        );
    }

    public function feeTypes()
    {
        return $this->belongsToMany(
            FeesType::class,
            'fee_concession_fee_types'
        );
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    // public function students()
    // {
    //     return $this->belongsToMany(
    //         Student::class,
    //         'student_fee_concessions'
    //     );
    // }
    public function students()
    {
        return $this->hasMany(StudentFeeConcession::class, 'fee_concession_id');
    }


}
