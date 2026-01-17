<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeInstallment extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function feesType()
    {
        return $this->belongsTo(FeesType::class,'fee_type_id');
    }

    public function studentFee()
    {
        return $this->belongsTo(
            StudentFee::class,
            'student_fee_id'
        );
    }
}
