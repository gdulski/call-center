<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleMetricsData
{
    public function __construct(
        public float $totalHours,
        public int $agentCount,
        public float $averageHoursPerAgent,
        public float $maxHoursPerAgent,
        public float $minHoursPerAgent,
        public array $hourlyCoverage
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            totalHours: $data['totalHours'],
            agentCount: $data['agentCount'],
            averageHoursPerAgent: $data['averageHoursPerAgent'],
            maxHoursPerAgent: $data['maxHoursPerAgent'],
            minHoursPerAgent: $data['minHoursPerAgent'],
            hourlyCoverage: $data['hourlyCoverage']
        );
    }

    public function toArray(): array
    {
        return [
            'totalHours' => $this->totalHours,
            'agentCount' => $this->agentCount,
            'averageHoursPerAgent' => $this->averageHoursPerAgent,
            'maxHoursPerAgent' => $this->maxHoursPerAgent,
            'minHoursPerAgent' => $this->minHoursPerAgent,
            'hourlyCoverage' => $this->hourlyCoverage
        ];
    }
}
