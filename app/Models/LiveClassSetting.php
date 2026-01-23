<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LiveClassSetting extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];

    protected $casts = [
        'recording_enabled' => 'boolean',
        'recorded_view_visibility' => 'array',
        'recorded_download_visibility' => 'array',
        'attendance' => 'array',
        'vdocipher' => 'array',
        'zoom_account_selection' => 'boolean',
    ];
    
    protected $hidden = ['created_at','updated_at','deleted_at'];
}
