<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Enums\AiFeature;

class AiUsageLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'clinic_id',
        'feature',
        'model',
        'input_tokens',
        'output_tokens',
        'cost_usd',
        'duration_ms',
        'created_at',
    ];

    protected $casts = [
        'input_tokens' => 'integer',
        'output_tokens' => 'integer',
        'cost_usd' => 'decimal:6',
        'duration_ms' => 'integer',
        'created_at' => 'datetime',
        'feature' => AiFeature::class,
    ];

    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }
}
