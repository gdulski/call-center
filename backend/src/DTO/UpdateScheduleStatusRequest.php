<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateScheduleStatusRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Status is required')]
        #[Assert\Type('string', message: 'Status must be a string')]
        public string $status
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            status: $data['status'] ?? ''
        );
    }
}
