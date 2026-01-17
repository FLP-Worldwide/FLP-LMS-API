<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeeLedger extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function studentFee()
    {
        return $this->belongsTo(StudentFee::class);
    }

    public function payment()
    {
        return $this->belongsTo(StudentFeePayment::class, 'payment_id');
    }

    public function installment()
    {
        return $this->belongsTo(
            \App\Models\StudentFeeInstallment::class,
            'student_fee_installment_id'
        );
    }



}
