<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FeesStructure extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function feesType()
    {
        return $this->belongsTo(FeesType::class);
    }

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }

    public function installments()
    {
        return $this->hasMany(FeesStructureInstallment::class);
    }

    public function batches()
    {
        return $this->belongsToMany(
            Batch::class,
            'fees_structure_batches'
        );
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }


}
