<?php

declare(strict_types=1);

namespace App\Modules\AI\DTO;

final class DrugInteractionResultDTO
{
    public function __construct(
        public readonly bool $ai_interaction_flag,
        public readonly ?string $ai_interaction_detail,
    ) {}

    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ai_interaction_flag: (bool) ($data['ai_interaction_flag'] ?? false),
            ai_interaction_detail: isset($data['ai_interaction_detail'])
                ? (string) $data['ai_interaction_detail']
                : null,
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'ai_interaction_flag' => $this->ai_interaction_flag,
            'ai_interaction_detail' => $this->ai_interaction_detail,
        ];
    }
}

