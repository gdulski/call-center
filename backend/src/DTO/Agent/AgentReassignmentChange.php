<?php

declare(strict_types=1);

namespace App\DTO\Agent;

final readonly class AgentReassignmentChange
{
    public function __construct(
        public int $assignmentId,
        public AgentInfo $oldAgent,
        public AgentInfo $newAgent,
        public string $date,
        public string $time,
        public float $duration
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            assignmentId: $data['assignmentId'],
            oldAgent: AgentInfo::fromArray($data['oldAgent']),
            newAgent: AgentInfo::fromArray($data['newAgent']),
            date: $data['date'],
            time: $data['time'],
            duration: $data['duration']
        );
    }

    public function toArray(): array
    {
        return [
            'assignmentId' => $this->assignmentId,
            'oldAgent' => $this->oldAgent->toArray(),
            'newAgent' => $this->newAgent->toArray(),
            'date' => $this->date,
            'time' => $this->time,
            'duration' => $this->duration
        ];
    }
}
