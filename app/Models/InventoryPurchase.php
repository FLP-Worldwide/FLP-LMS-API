<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventoryPurchase extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function items()
    {
        return $this->hasMany(InventoryPurchaseItem::class);
    }

    public function supplier()
    {
        return $this->belongsTo(InventorySupplier::class);
    }

    public function payments()
    {
        return $this->hasMany(InventoryPurchasePayment::class);
    }


}
