<?php

declare(strict_types=1);

namespace App\DTO\Agent;

use App\DTO\Agent\AgentReassignmentChange;
use App\DTO\Agent\AgentInfo;
use App\DTO\Agent\UnresolvedConflict;

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




