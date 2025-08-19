<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleMetricsResponse
{
    public function __construct(
        public array $metrics,
        public array $validation
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            metrics: $data['metrics'],
            validation: $data['validation']
        );
    }

    public function toArray(): array
    {
        return [
            'metrics' => $this->metrics,
            'validation' => $this->validation
        ];
    }
}
