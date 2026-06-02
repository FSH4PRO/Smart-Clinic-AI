<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

#[Fillable(['name', 'email', 'password', 'phone', 'role', 'avatar', 'phone_verified_at', 'last_login_at', 'email_verified_at', 'otp_code', 'otp_expires_at'])]
#[Hidden(['password', 'remember_token', 'otp_code', 'otp_expires_at'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasUlids, HasApiTokens, HasFactory, Notifiable, MustVerifyEmail;

    public function clinics(): HasMany
    {
        return $this->hasMany(Clinic::class, 'owner_id');
    }

    public function doctor(): HasOne
    {
        return $this->hasOne(Doctor::class);
    }

    public function patient(): HasOne
    {
        return $this->hasOne(Patient::class);
    }

    public function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
        ];
    }
}
