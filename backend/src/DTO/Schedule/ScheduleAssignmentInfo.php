<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleAssignmentInfo
{
    public function __construct(
        public int $id,
        public int $agentId,
        public string $agentName,
        public string $startTime,
        public string $endTime,
        public float $duration
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            agentId: $data['agentId'],
            agentName: $data['agentName'],
            startTime: $data['startTime'],
            endTime: $data['endTime'],
            duration: $data['duration']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'agentId' => $this->agentId,
            'agentName' => $this->agentName,
            'startTime' => $this->startTime,
            'endTime' => $this->endTime,
            'duration' => $this->duration
        ];
    }
}
