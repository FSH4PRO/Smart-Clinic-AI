<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ClinicBranch extends Model
{
    use HasFactory ,HasUuids;

    protected $fillable = [
        'clinic_id',
        'name',
        'address',
        'phone',
        'is_main',
    ];

    protected $casts = [
        'is_main' => 'boolean',
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    public function doctors(): HasMany
    {
        return $this->hasMany(Doctor::class, 'branch_id');
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'branch_id');
    }
}
