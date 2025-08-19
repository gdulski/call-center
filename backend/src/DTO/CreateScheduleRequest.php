<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class CreateScheduleRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'Queue type ID is required')]
        #[Assert\Type('integer', message: 'Queue type ID must be an integer')]
        public int $queueTypeId,
        
        #[Assert\NotBlank(message: 'Week start date is required')]
        #[Assert\Type('string', message: 'Week start date must be a string')]
        public string $weekStartDate,
        
        #[Assert\Choice(choices: ['ilp', 'heuristic'], message: 'Invalid optimization type. Allowed values: ilp, heuristic')]
        public string $optimizationType = 'ilp'
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            queueTypeId: (int)($data['queueTypeId'] ?? 0),
            weekStartDate: $data['weekStartDate'] ?? '',
            optimizationType: $data['optimizationType'] ?? 'ilp'
        );
    }
}
