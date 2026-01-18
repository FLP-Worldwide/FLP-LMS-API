<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ================= RELATIONS ================= */

    public function driver()
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function route()
    {
        return $this->belongsTo(BusRoute::class, 'bus_route_id');
    }

    /* ================= ACCESSORS ================= */

    public function getStatusAttribute()
    {
        return $this->bus_route_id ? 'assigned' : 'available';
    }

}
