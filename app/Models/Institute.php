<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institute extends Model
{
    use SoftDeletes;
    protected $guarded  = ['id'];

    public function users()
    {
        return $this->belongsToMany(User::class, 'institute_users')
            ->withPivot('role')
            ->withTimestamps();
    }
}
