<?php

declare(strict_types=1);

namespace App\Modules\AI\DTO;

final class TriageResultDTO
{
    
    public function __construct(
        public readonly int $urgency_score,
        public readonly string $recommended_specialty,
        public readonly array $extracted_symptoms,
        public readonly array $red_flags,
    ) {}

    
    public static function fromArray(array $data): self
    {
        $urgency = (int) ($data['urgency_score'] ?? 0);
        $specialty = (string) ($data['recommended_specialty'] ?? '');
        $symptoms = (array) ($data['extracted_symptoms'] ?? []);
        $redFlags = (array) ($data['red_flags'] ?? []);

        return new self(
            urgency_score: $urgency,
            recommended_specialty: $specialty,
            extracted_symptoms: array_values(array_map(static fn($v) => (string) $v, $symptoms)),
            red_flags: array_values(array_map(static fn($v) => (string) $v, $redFlags)),
        );
    }

   
    public function toArray(): array
    {
        return [
            'urgency_score' => $this->urgency_score,
            'recommended_specialty' => $this->recommended_specialty,
            'extracted_symptoms' => $this->extracted_symptoms,
            'red_flags' => $this->red_flags,
        ];
    }
}

