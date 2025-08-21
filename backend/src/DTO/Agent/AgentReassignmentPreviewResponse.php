<?php

declare(strict_types=1);

namespace App\DTO\Agent;

use App\DTO\Agent\AgentInfo;
use App\DTO\Agent\AgentReplacementInfo;

final readonly class AgentReassignmentPreviewResponse
{
    public function __construct(
        public int $assignmentId,
        public AgentInfo $currentAgent,
        public ?AgentReplacementInfo $suggestedReplacement,
        public string $date,
        public string $time,
        public float $duration,
        public bool $canBeReplaced
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            assignmentId: $data['assignmentId'],
            currentAgent: AgentInfo::fromArray($data['currentAgent']),
            suggestedReplacement: $data['suggestedReplacement'] ? AgentReplacementInfo::fromArray($data['suggestedReplacement']) : null,
            date: $data['date'],
            time: $data['time'],
            duration: $data['duration'],
            canBeReplaced: $data['canBeReplaced']
        );
    }

    public function toArray(): array
    {
        return [
            'assignmentId' => $this->assignmentId,
            'currentAgent' => $this->currentAgent->toArray(),
            'suggestedReplacement' => $this->suggestedReplacement?->toArray(),
            'date' => $this->date,
            'time' => $this->time,
            'duration' => $this->duration,
            'canBeReplaced' => $this->canBeReplaced
        ];
    }
}
