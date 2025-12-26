<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Subject extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function class()
    {
        return $this->belongsTo(\App\Models\ClassRoom::class, 'class_id');
    }
}
