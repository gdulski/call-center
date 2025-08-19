<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleGenerationResponse
{
    public function __construct(
        public int $scheduleId,
        public string $queueType,
        public string $weekStartDate,
        public string $weekEndDate,
        public int $predictionsCount,
        public int $availableAgentsCount,
        public int $assignmentsCount,
        public float $totalAssignedHours,
        public string $message
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            scheduleId: $data['scheduleId'],
            queueType: $data['queueType'],
            weekStartDate: $data['weekStartDate'],
            weekEndDate: $data['weekEndDate'],
            predictionsCount: $data['predictionsCount'],
            availableAgentsCount: $data['availableAgentsCount'],
            assignmentsCount: $data['assignmentsCount'],
            totalAssignedHours: $data['totalAssignedHours'],
            message: $data['message']
        );
    }

    public function toArray(): array
    {
        return [
            'scheduleId' => $this->scheduleId,
            'queueType' => $this->queueType,
            'weekStartDate' => $this->weekStartDate,
            'weekEndDate' => $this->weekEndDate,
            'predictionsCount' => $this->predictionsCount,
            'availableAgentsCount' => $this->availableAgentsCount,
            'assignmentsCount' => $this->assignmentsCount,
            'totalAssignedHours' => $this->totalAssignedHours,
            'message' => $this->message
        ];
    }
}
