<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleCreationResponse
{
    public function __construct(
        public int $id,
        public string $queueType,
        public string $weekStartDate,
        public string $weekEndDate,
        public string $status,
        public string $optimizationType,
        public array $generationResult,
        public array $optimizationResult
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            queueType: $data['queueType'],
            weekStartDate: $data['weekStartDate'],
            weekEndDate: $data['weekEndDate'],
            status: $data['status'],
            optimizationType: $data['optimizationType'],
            generationResult: $data['generationResult'],
            optimizationResult: $data['optimizationResult']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'queueType' => $this->queueType,
            'weekStartDate' => $this->weekStartDate,
            'weekEndDate' => $this->weekEndDate,
            'status' => $this->status,
            'optimizationType' => $this->optimizationType,
            'generationResult' => $this->generationResult,
            'optimizationResult' => $this->optimizationResult
        ];
    }
}
