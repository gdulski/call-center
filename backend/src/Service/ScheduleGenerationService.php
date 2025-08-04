<?php

namespace App\Service;

use App\Entity\Schedule;
use App\Entity\ScheduleShiftAssignment;
use App\Entity\User;
use App\Entity\AgentAvailability;
use App\Entity\CallQueueVolumePrediction;
use App\Repository\ScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\AgentAvailabilityRepository;
use App\Repository\CallQueueVolumePredictionRepository;
use App\Repository\QueueTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\ScheduleStatus;

class ScheduleGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduleRepository $scheduleRepository,
        private UserRepository $userRepository,
        private AgentAvailabilityRepository $availabilityRepository,
        private CallQueueVolumePredictionRepository $predictionRepository,
        private QueueTypeRepository $queueTypeRepository
    ) {}

    /**
     * Generuje harmonogram z przypisaniem agentów na podstawie predykcji zapotrzebowania
     */
    public function generateSchedule(int $scheduleId): array
    {
        $schedule = $this->scheduleRepository->find($scheduleId);
        if (!$schedule) {
            throw new \InvalidArgumentException('Schedule not found');
        }

        $queueType = $schedule->getQueueType();
        $weekStartDate = $schedule->getWeekStartDate();
        $weekEndDate = $schedule->getWeekEndDate();

        // Pobierz predykcje zapotrzebowania dla danego typu kolejki i tygodnia
        $predictions = $this->predictionRepository->findByQueueTypeAndDateRange(
            $queueType->getId(),
            $weekStartDate,
            $weekEndDate
        );

        // Pobierz dostępnych agentów dla danego typu kolejki
        $availableAgents = $this->userRepository->findAgentsByQueueType($queueType->getId());

        // Pobierz dostępności agentów w danym tygodniu
        $agentAvailabilities = $this->availabilityRepository->findByAgentsAndDateRange(
            array_map(fn($agent) => $agent->getId(), $availableAgents),
            $weekStartDate,
            $weekEndDate
        );

        // TODO: Implementacja algorytmu przypisywania agentów
        // Na razie zwracamy informację o tym, że funkcja została wywołana
        
        $result = [
            'scheduleId' => $scheduleId,
            'queueType' => $queueType->getName(),
            'weekStartDate' => $weekStartDate->format('Y-m-d'),
            'weekEndDate' => $weekEndDate->format('Y-m-d'),
            'predictionsCount' => count($predictions),
            'availableAgentsCount' => count($availableAgents),
            'availabilitiesCount' => count($agentAvailabilities),
            'message' => 'Funkcja generowania przypisań została wywołana - do implementacji'
        ];

        return $result;
    }

    /**
     * Pobiera predykcje zapotrzebowania dla danego typu kolejki i zakresu dat
     */
    private function getPredictions(int $queueTypeId, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // TODO: Implementacja pobierania predykcji
        return [];
    }

    /**
     * Pobiera dostępnych agentów dla danego typu kolejki
     */
    private function getAvailableAgents(int $queueTypeId): array
    {
        // TODO: Implementacja pobierania agentów
        return [];
    }

    /**
     * Pobiera dostępności agentów w danym zakresie dat
     */
    private function getAgentAvailabilities(array $agentIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        // TODO: Implementacja pobierania dostępności
        return [];
    }
} 