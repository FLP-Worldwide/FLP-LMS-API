<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes, BelongsToInstitute;

    //
    protected $guarded  = ['id'];

    public function details()
    {
        return $this->hasOne(StudentDetail::class);
    }
}
