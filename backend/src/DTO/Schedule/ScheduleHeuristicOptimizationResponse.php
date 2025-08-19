<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleHeuristicOptimizationResponse
{
    public function __construct(
        public int $scheduleId,
        public int $optimizedAssignmentsCount,
        public float $totalOptimizedHours,
        public string $message
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            scheduleId: $data['scheduleId'],
            optimizedAssignmentsCount: $data['optimizedAssignmentsCount'],
            totalOptimizedHours: $data['totalOptimizedHours'],
            message: $data['message']
        );
    }

    public function toArray(): array
    {
        return [
            'scheduleId' => $this->scheduleId,
            'optimizedAssignmentsCount' => $this->optimizedAssignmentsCount,
            'totalOptimizedHours' => $this->totalOptimizedHours,
            'message' => $this->message
        ];
    }
}
