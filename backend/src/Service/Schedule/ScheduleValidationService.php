<?php

declare(strict_types=1);

namespace App\Service\Schedule;

use App\DTO\Schedule\CreateScheduleRequest;
use App\Exception\ScheduleValidationException;
use App\Repository\ScheduleRepository;
use App\Repository\QueueTypeRepository;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class ScheduleValidationService
{
    public function __construct(
        private ValidatorInterface $validator,
        private QueueTypeRepository $queueTypeRepository,
        private ScheduleRepository $scheduleRepository
    ) {}

    public function validateCreateRequest(array $data): CreateScheduleRequest
    {
        $dto = CreateScheduleRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new ScheduleValidationException($errorMessages);
        }

        // Validate queue type exists
        $queueType = $this->queueTypeRepository->find($dto->queueTypeId);
        if (!$queueType) {
            throw new ScheduleValidationException(['Queue type not found']);
        }

        // Validate date format
        try {
            $weekStartDate = new \DateTime($dto->weekStartDate);
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Invalid date format']);
        }

        // Check if schedule already exists for this queue type and week
        $existingSchedule = $this->scheduleRepository->findByQueueTypeAndWeek(
            $queueType->getId(),
            $weekStartDate
        );
        
        if ($existingSchedule) {
            throw new ScheduleValidationException(['Schedule for this queue type and week already exists']);
        }

        return $dto;
    }
}
