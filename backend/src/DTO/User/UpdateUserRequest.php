<?php

declare(strict_types=1);

namespace App\DTO\User;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UpdateUserRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Name is required')]
        #[Assert\Length(min: 1, max: 255, minMessage: 'Name cannot be empty', maxMessage: 'Name cannot be longer than 255 characters')]
        public string $name,
        
        #[Assert\NotBlank(message: 'Role is required')]
        public string $role,
        
        #[Assert\All([
            new Assert\Type(['integer', 'array'], message: 'Queue type ID must be an integer or object with id and efficiencyScore')
        ])]
        public ?array $queueTypeIds = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: trim($data['name'] ?? ''),
            role: trim($data['role'] ?? ''),
            queueTypeIds: $data['queueTypeIds'] ?? null
        );
    }
}
