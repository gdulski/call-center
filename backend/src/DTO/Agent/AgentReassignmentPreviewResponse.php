<?php

declare(strict_types=1);

namespace App\DTO\Agent;

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

final readonly class AgentReplacementInfo
{
    public function __construct(
        public int $id,
        public string $name,
        public float $efficiencyScore
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            efficiencyScore: $data['efficiencyScore']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'efficiencyScore' => $this->efficiencyScore
        ];
    }
}
