<?php

namespace App\Models;

use App\Traits\BelongsToInstitute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use SoftDeletes, BelongsToInstitute;
    //
    protected $guarded  = ['id'];


    protected $casts = [
        'publish_at' => 'datetime',
        'due_at' => 'datetime',
        'allow_late_submission' => 'boolean',
        'evaluation_required' => 'boolean',
    ];

    public function resources()
    {
        return $this->hasMany(AssignmentResource::class);
    }

    public function course()  { return $this->belongsTo(Course::class); }
    public function batch()   { return $this->belongsTo(Batch::class); }
    public function teacher() { return $this->belongsTo(User::class, 'teacher_id'); }
    public function subject() { return $this->belongsTo(Subject::class); }

}
