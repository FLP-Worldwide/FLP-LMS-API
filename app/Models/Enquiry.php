<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enquiry extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function details()
    {
        return $this->hasOne(EnquiryDetail::class);
    }

    public function followUps()
    {
        return $this->hasMany(EnquiryFollowUp::class);
    }
}
