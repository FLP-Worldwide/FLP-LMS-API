<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Session\DatabaseSessionHandler;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $guarded = ['id'];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /* ---------- Institute Relations ---------- */

    public function institutes()
    {
        return $this->belongsToMany(Institute::class, 'institute_users')
            ->withPivot(['role', 'role_id'])
            ->withTimestamps();
    }

    public function instituteUsers()
    {
        return $this->hasMany(InstituteUser::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    // ✅ SINGLE institute record (this is what APIs use)
    public function instituteUser()
    {
        return $this->hasOne(InstituteUser::class)
            ->where('institute_id', auth()->user()->institute_id);
    }




    /* ---------- Staff ---------- */

    public function staffDetail()
    {
        return $this->hasOne(StaffDetail::class);
    }

    /* ---------- Sessions ---------- */

    public function sessions()
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function lastLoginAt()
    {
        return optional(
            $this->sessions()->orderByDesc('last_activity')->first()
        )->last_activity;
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permissions'
        )->withTimestamps();
    }

    public function getTempPasswordAttribute($value)
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /* ---------- JWT ---------- */

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role,
        ];
    }
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

}
