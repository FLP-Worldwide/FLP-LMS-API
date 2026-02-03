<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomFieldValue extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function field()
    {
        return $this->belongsTo(CustomField::class, 'custom_field_id');
    }

    public function enquiry()
    {
        return $this->belongsTo(Enquiry::class);
    }

}
