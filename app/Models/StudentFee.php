<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentFee extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function installments()
    {
        return $this->hasMany(StudentFeeInstallment::class);
    }

    public function structure()
    {
        return $this->belongsTo(FeesStructure::class, 'fees_structure_id');
    }

    public function ledgers()
    {
        return $this->hasMany(StudentFeeLedger::class);
    }



}
