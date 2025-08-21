<?php

declare(strict_types=1);

namespace App\DTO\Agent;

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
