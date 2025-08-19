<?php

declare(strict_types=1);

namespace App\Service\Agent;

use App\DTO\CreateAgentAvailabilityRequest;
use App\DTO\UpdateAgentAvailabilityRequest;
use App\Exception\AgentAvailabilityValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class AgentAvailabilityValidationService
{
    public function __construct(
        private ValidatorInterface $validator
    ) {}

    public function validateCreateRequest(array $data): CreateAgentAvailabilityRequest
    {
        $dto = CreateAgentAvailabilityRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new AgentAvailabilityValidationException($errorMessages);
        }

        return $dto;
    }

    public function validateUpdateRequest(array $data): UpdateAgentAvailabilityRequest
    {
        $dto = UpdateAgentAvailabilityRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new AgentAvailabilityValidationException($errorMessages);
        }

        return $dto;
    }
}
