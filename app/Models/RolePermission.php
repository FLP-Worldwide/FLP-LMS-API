<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RolePermission extends Model
{
    use SoftDeletes;
    //
    protected $guarded  = ['id'];
protected $hidden = ['created_at','updated_at','deleted_at'];
     public function permission()
    {
        return $this->belongsTo(Permission::class);
    }
}
