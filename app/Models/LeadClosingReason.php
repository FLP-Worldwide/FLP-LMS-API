<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\BelongsToInstitute;

class LeadClosingReason extends Model
{
    use SoftDeletes, BelongsToInstitute;

    protected $guarded = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }
}
