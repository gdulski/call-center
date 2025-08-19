<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleValidationData
{
    public function __construct(
        public bool $isValid,
        public array $violations,
        public int $totalViolations
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            isValid: $data['isValid'],
            violations: $data['violations'],
            totalViolations: $data['totalViolations']
        );
    }

    public function toArray(): array
    {
        return [
            'isValid' => $this->isValid,
            'violations' => $this->violations,
            'totalViolations' => $this->totalViolations
        ];
    }
}
