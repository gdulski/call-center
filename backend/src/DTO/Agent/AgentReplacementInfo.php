<?php

declare(strict_types=1);

namespace App\DTO\Agent;

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
