<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleOptimizationResponse
{
    public function __construct(
        public int $assignmentsCount,
        public float $totalHours,
        public array $metrics,
        public array $validation
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            assignmentsCount: $data['assignmentsCount'],
            totalHours: $data['totalHours'],
            metrics: $data['metrics'],
            validation: $data['validation']
        );
    }

    public function toArray(): array
    {
        return [
            'assignmentsCount' => $this->assignmentsCount,
            'totalHours' => $this->totalHours,
            'metrics' => $this->metrics,
            'validation' => $this->validation
        ];
    }
}
