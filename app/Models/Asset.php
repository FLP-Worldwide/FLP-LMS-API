<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function location()
    {
        return $this->belongsTo(AssetLocation::class, 'asset_location_id');
    }

    public function category()
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }


    public function suppliers()
    {
        return $this->belongsToMany(
            Supplier::class,
            'supplier_asset_items',
            'asset_item_id',
            'supplier_id'
        );
    }



}
