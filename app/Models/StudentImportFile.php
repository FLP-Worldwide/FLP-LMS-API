<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StudentImportFile extends Model
{
    use BelongsToInstitute;
    //
    protected $guarded  = ['id'];

    public $total = 0;
    public $success = 0;
    public $failed = 0;
    public $fileId = null;
}
