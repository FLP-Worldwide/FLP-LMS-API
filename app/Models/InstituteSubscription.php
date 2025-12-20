<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InstituteSubscription extends Model
{
    use SoftDeletes;
    //
    protected $guarded  = ['id'];

    public function institute()
    {
        return $this->belongsTo(Institute::class);
    }

    public function plan()
    {
        return $this->belongsTo(Plan::class);
    }
}
