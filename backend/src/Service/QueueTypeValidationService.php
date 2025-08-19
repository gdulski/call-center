<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateQueueTypeRequest;
use App\DTO\UpdateQueueTypeRequest;
use App\Entity\QueueType;
use App\Exception\QueueTypeValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class QueueTypeValidationService
{
    public function __construct(
        private QueueTypeService $queueTypeService,
        private ValidatorInterface $validator
    ) {}

    public function validateCreateRequest(array $data): CreateQueueTypeRequest
    {
        $dto = CreateQueueTypeRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new QueueTypeValidationException($errorMessages);
        }

        if (!$this->queueTypeService->isNameUnique($dto->name)) {
            throw new QueueTypeValidationException(['Queue type with this name already exists']);
        }

        return $dto;
    }

    public function validateUpdateRequest(array $data, QueueType $queueType): UpdateQueueTypeRequest
    {
        $dto = UpdateQueueTypeRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new QueueTypeValidationException($errorMessages);
        }

        if (!$this->queueTypeService->isNameUnique($dto->name, $queueType)) {
            throw new QueueTypeValidationException(['Queue type with this name already exists']);
        }

        return $dto;
    }
}
