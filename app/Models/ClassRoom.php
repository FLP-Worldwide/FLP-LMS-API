<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassRoom extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];


    protected $casts = [
        'created_on' => 'date',
        'is_active'  => 'boolean',
    ];


}
