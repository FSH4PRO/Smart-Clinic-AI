<?php

namespace App\Models;

use App\Enums\ClinicSubscriptionPlan;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Clinic extends Model
{
    use HasFactory, SoftDeletes ,HasUuids;

    protected $fillable = [
        'owner_id',
        'name',
        'slug',
        'logo',
        'license_number',
        'subscription_plan',
        'subscription_ends_at',
        'country',
        'city',
        'address',
        'latitude',
        'longitude',
        'settings',
    ];

    protected $casts = [
        'subscription_ends_at' => 'date',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'subscription_plan' => ClinicSubscriptionPlan::class,
        'settings' => 'array',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function branches(): HasMany
    {
        return $this->hasMany(ClinicBranch::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function pharmacies(): HasMany
    {
        return $this->hasMany(Pharmacy::class);
    }

    public function aiUsageLogs(): HasMany
    {
        return $this->hasMany(AiUsageLog::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
