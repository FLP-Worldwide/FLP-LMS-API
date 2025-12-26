<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EnquiryFollowUp extends Model
{
    use SoftDeletes;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

}
