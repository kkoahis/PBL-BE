<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'phone_number',
        'address',
        'date_of_birth',
        'avatar',
        'gender',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    {
        $url = 'pbl-be-production.up.railway.app/api/reset-password?token=' . $token;

        $this->notify(new ResetPasswordNotification($url));
    }

    public function hasRole($role)
    {
        return $this->role == $role;
    }

    public function booking()
    {
        return $this->hasMany(Booking::class,);
    }

    public function hotel()
    {
        return $this->hasMany(Hotel::class,);
    }

    public function review()
    {
        return $this->hasMany(Review::class,);
    }

    public function reply()
    {
        return $this->hasMany(Reply::class,);
    }
}
