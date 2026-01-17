<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFeePayment extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function ledgers()
    {
        return $this->hasMany(StudentFeeLedger::class, 'payment_id');
    }

}
