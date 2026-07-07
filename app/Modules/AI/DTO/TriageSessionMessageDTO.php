<?php

declare(strict_types=1);

namespace App\Modules\AI\DTO;

final class TriageSessionMessageDTO
{
    /**
     * @param 'patient'|'ai' $role
     * @param string $content
     * @param string|null $timestamp ISO-8601 string (controller may pass Carbon)
     */
    public function __construct(
        public readonly string $role,
        public readonly string $content,
        public readonly ?string $timestamp,
    ) {}

    /**
     * Build DTO from controller with Carbon/strings.
     *
     * @param 'patient'|'ai' $role
     * @param string $content
     * @param \Illuminate\Support\Carbon|\DateTimeInterface|string|null $timestamp
     * @return self
     */
    public static function fromControllerValues(string $role, string $content, mixed $timestamp): self
    {
        $ts = null;
        if ($timestamp instanceof \DateTimeInterface) {
            $ts = $timestamp->format(DATE_ATOM);
        } elseif (is_string($timestamp)) {
            $ts = $timestamp;
        }

        return new self(role: $role, content: $content, timestamp: $ts);
    }

    /**
     * @param array<string,mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $role = (string) ($data['role'] ?? 'patient');
        $content = (string) ($data['content'] ?? '');
        $timestamp = isset($data['timestamp']) ? (string) $data['timestamp'] : null;

        return new self(role: $role, content: $content, timestamp: $timestamp);
    }

    /**
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return [
            'role' => $this->role,
            'content' => $this->content,
            'timestamp' => $this->timestamp,
        ];
    }
}
