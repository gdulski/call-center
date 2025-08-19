<?php

declare(strict_types=1);

namespace App\Service\QueueType;

use App\Entity\QueueType;
use App\Exception\QueueTypeValidationException;
use App\Repository\QueueTypeRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class QueueTypeService
{
    public function __construct(
        private QueueTypeRepository $queueTypeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createQueueType(string $name): QueueType
    {
        try {
            $queueType = new QueueType();
            $queueType->setName($name);

            $this->entityManager->persist($queueType);
            $this->entityManager->flush();

            return $queueType;
        } catch (\Exception $e) {
            throw new QueueTypeValidationException(['Failed to create queue type: ' . $e->getMessage()]);
        }
    }

    public function updateQueueType(QueueType $queueType, string $name): void
    {
        try {
            $queueType->setName($name);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new QueueTypeValidationException(['Failed to update queue type: ' . $e->getMessage()]);
        }
    }

    public function findQueueTypeById(int $id): ?QueueType
    {
        return $this->queueTypeRepository->find($id);
    }

    public function isNameUnique(string $name, ?QueueType $excludeQueueType = null): bool
    {
        $existingQueueType = $this->queueTypeRepository->findOneBy(['name' => $name]);

        if (!$existingQueueType) {
            return true;
        }

        if ($excludeQueueType && $existingQueueType->getId() === $excludeQueueType->getId()) {
            return true;
        }

        return false;
    }
}
