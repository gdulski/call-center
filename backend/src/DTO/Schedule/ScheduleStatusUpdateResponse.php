<?php

declare(strict_types=1);

namespace App\DTO\Schedule;

final readonly class ScheduleStatusUpdateResponse
{
    public function __construct(
        public int $id,
        public string $status
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            status: $data['status']
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status
        ];
    }
}
