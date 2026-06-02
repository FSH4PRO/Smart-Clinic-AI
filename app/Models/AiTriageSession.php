<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiTriageSession extends Model
{
    use HasFactory ,HasUuids;

    protected $fillable = [
        'appointment_id',
        'messages',
        'extracted_symptoms',
        'triage_result',
        'tokens_used',
        'completed_at',
    ];

    protected $casts = [
        'messages' => 'array',
        'extracted_symptoms' => 'array',
        'triage_result' => 'array',
        'completed_at' => 'datetime',
        'tokens_used' => 'integer',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }
}
