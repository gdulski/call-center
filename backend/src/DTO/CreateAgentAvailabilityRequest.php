<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateAgentAvailabilityRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Agent ID is required')]
        #[Assert\Type('integer', message: 'Agent ID must be an integer')]
        public int $agentId,
        
        #[Assert\NotBlank(message: 'Start date is required')]
        #[Assert\Type('string', message: 'Start date must be a string')]
        public string $startDate,
        
        #[Assert\NotBlank(message: 'End date is required')]
        #[Assert\Type('string', message: 'End date must be a string')]
        public string $endDate
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            agentId: (int)($data['agentId'] ?? 0),
            startDate: $data['startDate'] ?? '',
            endDate: $data['endDate'] ?? ''
        );
    }
}
