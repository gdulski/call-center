<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateQueueTypeRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Name cannot be empty', maxMessage: 'Name cannot be longer than 255 characters')]
        public string $name
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? '')
        );
    }
}
