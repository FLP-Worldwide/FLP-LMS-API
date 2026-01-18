<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BusRoute extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];
    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function stops()
    {
        return $this->hasMany(BusRouteStop::class)
            ->orderBy('stop_order');
    }

}
