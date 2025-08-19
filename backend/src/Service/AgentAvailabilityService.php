<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\AgentAvailability;
use App\Entity\User;
use App\Exception\AgentAvailabilityValidationException;
use App\Repository\AgentAvailabilityRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

final readonly class AgentAvailabilityService
{
    public function __construct(
        private AgentAvailabilityRepository $availabilityRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createAvailability(int $agentId, string $startDate, string $endDate): AgentAvailability
    {
        try {
            $agent = $this->userRepository->find($agentId);
            if (!$agent) {
                throw new AgentAvailabilityValidationException(['Agent not found']);
            }

            $startDateTime = $this->parseAndValidateDate($startDate);
            $endDateTime = $this->parseAndValidateDate($endDate);

            if ($startDateTime >= $endDateTime) {
                throw new AgentAvailabilityValidationException(['Start date must be before end date']);
            }

            // Check for overlapping availability periods
            if ($this->hasOverlappingPeriods($agent, $startDateTime, $endDateTime)) {
                throw new AgentAvailabilityValidationException(['Availability period overlaps with existing period']);
            }

            $availability = new AgentAvailability();
            $availability->setAgent($agent);
            $availability->setStartDate($startDateTime);
            $availability->setEndDate($endDateTime);

            $this->entityManager->persist($availability);
            $this->entityManager->flush();

            return $availability;
        } catch (AgentAvailabilityValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AgentAvailabilityValidationException(['Failed to create availability: ' . $e->getMessage()]);
        }
    }

    public function updateAvailability(AgentAvailability $availability, string $startDate, string $endDate): void
    {
        try {
            $startDateTime = $this->parseAndValidateDate($startDate);
            $endDateTime = $this->parseAndValidateDate($endDate);

            if ($startDateTime >= $endDateTime) {
                throw new AgentAvailabilityValidationException(['Start date must be before end date']);
            }

            // Check for overlapping availability periods (excluding current one)
            if ($this->hasOverlappingPeriods($availability->getAgent(), $startDateTime, $endDateTime, $availability->getId())) {
                throw new AgentAvailabilityValidationException(['Availability period overlaps with existing period']);
            }

            $availability->setStartDate($startDateTime);
            $availability->setEndDate($endDateTime);

            $this->entityManager->flush();
        } catch (AgentAvailabilityValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AgentAvailabilityValidationException(['Failed to update availability: ' . $e->getMessage()]);
        }
    }

    public function findAvailabilityById(int $id): ?AgentAvailability
    {
        return $this->availabilityRepository->find($id);
    }

    public function findAvailabilitiesByAgent(?int $agentId = null): array
    {
        if ($agentId) {
            $agent = $this->userRepository->find($agentId);
            if (!$agent) {
                return [];
            }
            return $this->availabilityRepository->findBy(['agent' => $agent]);
        }

        return $this->availabilityRepository->findAll();
    }

    public function deleteAvailability(AgentAvailability $availability): void
    {
        try {
            $this->entityManager->remove($availability);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new AgentAvailabilityValidationException(['Failed to delete availability: ' . $e->getMessage()]);
        }
    }

    private function parseAndValidateDate(string $dateString): \DateTime
    {
        try {
            $dateTime = new \DateTime($dateString);
            return $this->roundToFullHour($dateTime);
        } catch (\Exception $e) {
            throw new AgentAvailabilityValidationException(['Invalid date format']);
        }
    }

    private function hasOverlappingPeriods(User $agent, \DateTime $startDate, \DateTime $endDate, ?int $excludeId = null): bool
    {
        $queryBuilder = $this->availabilityRepository->createQueryBuilder('a')
            ->where('a.agent = :agent')
            ->andWhere('(a.startDate <= :endDate AND a.endDate >= :startDate)')
            ->setParameter('agent', $agent)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate);

        if ($excludeId !== null) {
            $queryBuilder->andWhere('a.id != :currentId')
                ->setParameter('currentId', $excludeId);
        }

        $overlapping = $queryBuilder->getQuery()->getResult();

        return !empty($overlapping);
    }

    /**
     * Zaokrągla datę do pełnej godziny (ustaw minuty, sekundy i mikrosekundy na 0)
     */
    private function roundToFullHour(\DateTimeInterface $dateTime): \DateTime
    {
        $rounded = new \DateTime($dateTime->format('Y-m-d H:i:s'), $dateTime->getTimezone());
        $rounded->setTime(
            (int)$rounded->format('H'), // godzina
            0, // minuty = 0
            0, // sekundy = 0
            0  // mikrosekundy = 0
        );
        return $rounded;
    }
}
