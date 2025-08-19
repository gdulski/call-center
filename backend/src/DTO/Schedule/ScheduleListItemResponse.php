<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleListItemResponse
{
    public function __construct(
        public int $id,
        public QueueTypeInfo $queueType,
        public string $weekStartDate,
        public string $weekEndDate,
        public string $weekIdentifier,
        public string $status,
        public float $totalAssignedHours,
        public int $assignmentsCount
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            queueType: QueueTypeInfo::fromArray($data['queueType']),
            weekStartDate: $data['weekStartDate'],
            weekEndDate: $data['weekEndDate'],
            weekIdentifier: $data['weekIdentifier'],
            status: $data['status'],
            totalAssignedHours: $data['totalAssignedHours'],
            assignmentsCount: $data['assignmentsCount']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'queueType' => $this->queueType->toArray(),
            'weekStartDate' => $this->weekStartDate,
            'weekEndDate' => $this->weekEndDate,
            'weekIdentifier' => $this->weekIdentifier,
            'status' => $this->status,
            'totalAssignedHours' => $this->totalAssignedHours,
            'assignmentsCount' => $this->assignmentsCount
        ];
    }
}

final readonly class QueueTypeInfo
{
    public function __construct(
        public int $id,
        public string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name
        ];
    }
}
