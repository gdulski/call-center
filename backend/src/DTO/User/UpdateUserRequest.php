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
            new Assert\Type('array'),
            new Assert\Collection([
                'id' => [new Assert\NotBlank(), new Assert\Type('integer')],
                'efficiencyScore' => [new Assert\Type('float'), new Assert\Range(min: 0.0, max: 100.0)]
            ])
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
