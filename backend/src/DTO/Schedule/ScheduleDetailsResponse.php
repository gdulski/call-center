<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

use App\DTO\QueueType\QueueTypeInfo;

final readonly class ScheduleDetailsResponse
{
    public function __construct(
        public int $id,
        public QueueTypeInfo $queueType,
        public string $weekStartDate,
        public string $weekEndDate,
        public string $status,
        public float $totalAssignedHours,
        public array $assignments
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            queueType: QueueTypeInfo::fromArray($data['queueType']),
            weekStartDate: $data['weekStartDate'],
            weekEndDate: $data['weekEndDate'],
            status: $data['status'],
            totalAssignedHours: $data['totalAssignedHours'],
            assignments: array_map(fn(array $assignment) => ScheduleAssignmentInfo::fromArray($assignment), $data['assignments'])
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'queueType' => $this->queueType->toArray(),
            'weekStartDate' => $this->weekStartDate,
            'weekEndDate' => $this->weekEndDate,
            'status' => $this->status,
            'totalAssignedHours' => $this->totalAssignedHours,
            'assignments' => array_map(fn(ScheduleAssignmentInfo $assignment) => $assignment->toArray(), $this->assignments)
        ];
    }
}
