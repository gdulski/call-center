<?php

declare(strict_types=1);

namespace App\DTO\Agent;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class AgentReassignmentRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Agent ID is required')]
        #[Assert\Type('integer', message: 'Agent ID must be an integer')]
        public int $agentId,
        
        #[Assert\NotBlank(message: 'New availability is required')]
        #[Assert\Type('array', message: 'New availability must be an array')]
        public array $newAvailability
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agentId: (int)($data['agentId'] ?? 0),
            newAvailability: $data['newAvailability'] ?? []
        );
    }
}
