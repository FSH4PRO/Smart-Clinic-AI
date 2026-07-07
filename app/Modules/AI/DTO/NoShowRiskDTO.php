<?php

declare(strict_types=1);

namespace App\Modules\AI\DTO;

final class NoShowRiskDTO
{
    /**
     * @param float $probability 0.00 - 1.00
     */
    public function __construct(public readonly float $probability) {}

    /**
     * @param array<string,mixed> $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $p = (float) ($data['no_show_probability'] ?? 0.0);
        $p = max(0.0, min(1.0, $p));
        return new self($p);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'no_show_probability' => $this->probability,
        ];
    }
}

