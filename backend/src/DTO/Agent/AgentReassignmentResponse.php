<?php

declare(strict_types=1);

namespace App\DTO\Agent;

final readonly class AgentReassignmentResponse
{
    public function __construct(
        public bool $success,
        public array $changes,
        public array $unresolvedConflicts,
        public string $message
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            success: $data['success'],
            changes: array_map(fn(array $change) => AgentReassignmentChange::fromArray($change), $data['changes']),
            unresolvedConflicts: array_map(fn(array $conflict) => UnresolvedConflict::fromArray($conflict), $data['unresolvedConflicts']),
            message: $data['message']
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'changes' => array_map(fn(AgentReassignmentChange $change) => $change->toArray(), $this->changes),
            'unresolvedConflicts' => array_map(fn(UnresolvedConflict $conflict) => $conflict->toArray(), $this->unresolvedConflicts),
            'message' => $this->message
        ];
    }
}

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

final readonly class AgentInfo
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

final readonly class UnresolvedConflict
{
    public function __construct(
        public int $assignmentId,
        public string $date,
        public string $time,
        public string $reason
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            assignmentId: $data['assignmentId'],
            date: $data['date'],
            time: $data['time'],
            reason: $data['reason']
        );
    }

    public function toArray(): array
    {
        return [
            'assignmentId' => $this->assignmentId,
            'date' => $this->date,
            'time' => $this->time,
            'reason' => $this->reason
        ];
    }
}
