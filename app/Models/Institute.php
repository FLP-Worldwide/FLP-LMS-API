<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use SoftDeletes;
    protected $guarded  = ['id'];
    protected $hidden = ['created_at','updated_at','deleted_at'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'institute_users')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subscription()
    {
        return $this->hasOne(InstituteSubscription::class);
    }

    public function leadSourceTypes()
{
    return $this->hasMany(LeadSourceType::class);
}

}
