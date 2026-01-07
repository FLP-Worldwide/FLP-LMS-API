<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function categories()
    {
        return $this->belongsToMany(
            AssetCategory::class,
            'supplier_categories'
        );
    }

    public function assetItems()
    {
        return $this->belongsToMany(
            Asset::class,
            'supplier_asset_items',
            'supplier_id',
            'asset_item_id'
        );
    }





}
