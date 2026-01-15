<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventorySale extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function items()
    {
        return $this->hasMany(InventorySaleItem::class);
    }

    public function payment()
    {
        return $this->hasOne(InventorySalePayment::class);
    }

    public function staff()
    {
        return $this->belongsTo(Teacher::class, 'user_id');
    }
}
