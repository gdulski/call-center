<?php

namespace App\Service;

use App\Entity\Schedule;
use App\Entity\ScheduleShiftAssignment;
use App\Entity\User;
use App\Entity\AgentAvailability;
use App\Entity\CallQueueVolumePrediction;
use App\Entity\AgentQueueType;
use App\Repository\ScheduleRepository;
use App\Repository\UserRepository;
use App\Repository\AgentAvailabilityRepository;
use App\Repository\CallQueueVolumePredictionRepository;
use App\Repository\QueueTypeRepository;
use App\Repository\AgentQueueTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Enum\ScheduleStatus;
use Psr\Log\LoggerInterface;
use App\Enum\UserRole;

class ScheduleGenerationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduleRepository $scheduleRepository,
        private UserRepository $userRepository,
        private AgentAvailabilityRepository $availabilityRepository,
        private CallQueueVolumePredictionRepository $predictionRepository,
        private QueueTypeRepository $queueTypeRepository,
        private AgentQueueTypeRepository $agentQueueTypeRepository,
        private LoggerInterface $logger
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

        // Usuń istniejące przypisania dla tego harmonogramu
        $this->clearExistingAssignments($schedule);

        $queueType = $schedule->getQueueType();
        $weekStartDate = $schedule->getWeekStartDate();
        $weekEndDate = $schedule->getWeekEndDate();

        // Pobierz predykcje zapotrzebowania dla danego typu kolejki i tygodnia
        $predictions = $this->predictionRepository->findByQueueTypeAndDateRange(
            $queueType->getId(),
            $weekStartDate,
            $weekEndDate
        );
        

        // Pobierz dostępnych agentów dla danego typu kolejki z ich efektywnością
        $availableAgents = $this->getAvailableAgentsWithEfficiency($queueType->getId());

        $this->logger->info('API Debug', [
            array_map(fn($agent) => $agent['user']->getId(), $availableAgents),
            $weekStartDate,
            $weekEndDate
        ]);

        // Pobierz dostępności agentów w danym tygodniu
        $agentAvailabilities = $this->availabilityRepository->findByAgentsAndDateRange(
            array_map(fn($agent) => $agent['user']->getId(), $availableAgents),
            $weekStartDate,
            $weekEndDate
        );

        // Grupuj predykcje według godzin
        $hourlyPredictions = $this->groupPredictionsByHour($predictions);

        // Grupuj dostępności według godzin
        $hourlyAvailabilities = $this->groupAvailabilitiesByHour($agentAvailabilities);

        $this->logger->info('API Debug', ['hourlyAvailabilities' => $hourlyAvailabilities]);
        $this->logger->info('API Debug', ['hourlyPredictions' => $hourlyPredictions]);

        // Generuj harmonogram używając heurystyk (podstawowe generowanie)
        $assignments = $this->generateOptimalAssignments(
            $schedule,
            $hourlyPredictions,
            $availableAgents,
            $hourlyAvailabilities
        );

        // Zapisz przypisania do bazy danych
        $this->saveAssignments($assignments);

        // Aktualizuj status harmonogramu
        $schedule->setStatus(ScheduleStatus::GENERATED);
        $this->entityManager->flush();

        return [
            'scheduleId' => $scheduleId,
            'queueType' => $queueType->getName(),
            'weekStartDate' => $weekStartDate->format('Y-m-d'),
            'weekEndDate' => $weekEndDate->format('Y-m-d'),
            'predictionsCount' => count($predictions),
            'availableAgentsCount' => count($availableAgents),
            'assignmentsCount' => count($assignments),
            'totalAssignedHours' => $schedule->getTotalAssignedHours(),
            'message' => 'Harmonogram został wygenerowany pomyślnie'
        ];
    }

    /**
     * Usuwa istniejące przypisania dla harmonogramu
     */
    private function clearExistingAssignments(Schedule $schedule): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(ScheduleShiftAssignment::class, 'ssa')
           ->where('ssa.schedule = :schedule')
           ->setParameter('schedule', $schedule);
        $qb->getQuery()->execute();
    }

    /**
     * Pobiera dostępnych agentów z ich efektywnością dla danej kolejki
     */
    private function getAvailableAgentsWithEfficiency(int $queueTypeId): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('u', 'aqt.efficiencyScore')
           ->from(User::class, 'u')
           ->join(AgentQueueType::class, 'aqt', 'WITH', 'aqt.user = u')
           ->where('aqt.queueType = :queueTypeId')
           ->andWhere('u.role = :role')
           ->setParameter('queueTypeId', $queueTypeId)
           ->setParameter('role', UserRole::AGENT)
           ->orderBy('aqt.efficiencyScore', 'DESC');

        $results = $qb->getQuery()->getResult();
        
        $agents = [];
        foreach ($results as $result) {
            $agents[] = [
                'user' => $result[0],
                'efficiencyScore' => $result['efficiencyScore']
            ];
        }
        
        return $agents;
    }

    /**
     * Grupuje predykcje według godzin
     */
    private function groupPredictionsByHour(array $predictions): array
    {
        $grouped = [];
        foreach ($predictions as $prediction) {
            $hourKey = $prediction->getHour()->format('Y-m-d H:i');
            $grouped[$hourKey] = $prediction->getExpectedCalls();
        }
        return $grouped;
    }

    /**
     * Grupuje dostępności według godzin
     */
    private function groupAvailabilitiesByHour(array $availabilities): array
    {
        $grouped = [];
        foreach ($availabilities as $availability) {
            $agentId = $availability->getAgent()->getId();
            $start = $availability->getStartDate();
            $end = $availability->getEndDate();
            
            // Generuj klucze godzinowe dla każdej godziny w zakresie dostępności
            $current = clone $start;
            while ($current <= $end) {
                $hourKey = $current->format('Y-m-d H:i');
                if (!isset($grouped[$hourKey])) {
                    $grouped[$hourKey] = [];
                }
                $grouped[$hourKey][] = $agentId;
                $current->modify('+1 hour');
            }
        }
        return $grouped;
    }

    /**
     * Generuje optymalne przypisania używając algorytmu ILP + heurystyk
     */
    private function generateOptimalAssignments(
        Schedule $schedule,
        array $hourlyPredictions,
        array $availableAgents,
        array $hourlyAvailabilities
    ): array {
        $assignments = [];
        
        // Sortuj agentów według efektywności (najlepsi pierwsi)
        usort($availableAgents, function($a, $b) {
            return $b['efficiencyScore'] <=> $a['efficiencyScore'];
        });

        // Dla każdej godziny z predykcją
        foreach ($hourlyPredictions as $hourKey => $expectedCalls) {
            $hourDateTime = \DateTime::createFromFormat('Y-m-d H:i', $hourKey);
            $availableAgentIds = $hourlyAvailabilities[$hourKey] ?? [];
            
            if (empty($availableAgentIds)) {
                continue; // Brak dostępnych agentów w tej godzinie
            }

            // Filtruj dostępnych agentów dla tej godziny
            $hourlyAvailableAgents = array_filter($availableAgents, function($agent) use ($availableAgentIds) {
                return in_array($agent['user']->getId(), $availableAgentIds);
            });

            // Oblicz wymagane godziny pracy na podstawie predykcji
            $requiredHours = $this->calculateRequiredHours($expectedCalls, $hourlyAvailableAgents);
            
            // Przydziel agentów używając heurystyk
            $hourAssignments = $this->assignAgentsToHour(
                $schedule,
                $hourDateTime,
                $hourlyAvailableAgents,
                $requiredHours
            );
            
            $assignments = array_merge($assignments, $hourAssignments);
        }

        return $assignments;
    }

    /**
     * Oblicza wymagane godziny pracy na podstawie predykcji i efektywności agentów
     */
    private function calculateRequiredHours(int $expectedCalls, array $availableAgents): float
    {
        if (empty($availableAgents)) {
            return 0;
        }

        // Średnia efektywność dostępnych agentów
        $avgEfficiency = array_sum(array_column($availableAgents, 'efficiencyScore')) / count($availableAgents);
        
        // Zakładamy, że jeden agent może obsłużyć średnio 10 połączeń na godzinę
        // (można to dostosować na podstawie danych historycznych)
        $callsPerHourPerAgent = 10 * $avgEfficiency;
        
        if ($callsPerHourPerAgent <= 0) {
            return 0;
        }

        return $expectedCalls / $callsPerHourPerAgent;
    }

    /**
     * Przydziela agentów do konkretnej godziny
     */
    private function assignAgentsToHour(
        Schedule $schedule,
        \DateTimeInterface $hourDateTime,
        array $availableAgents,
        float $requiredHours
    ): array {
        $assignments = [];
        $remainingHours = $requiredHours;
        
        // Maksymalna liczba godzin na agenta w jednej godzinie (zazwyczaj 1)
        $maxHoursPerAgent = 1.0;
        
        foreach ($availableAgents as $agentData) {
            if ($remainingHours <= 0) {
                break;
            }
            
            $agent = $agentData['user'];
            $efficiency = $agentData['efficiencyScore'];
            
            // Oblicz godziny do przydzielenia dla tego agenta
            $hoursToAssign = min($maxHoursPerAgent, $remainingHours);
            
            // Utwórz przypisanie
            $assignment = new ScheduleShiftAssignment();
            $assignment->setSchedule($schedule);
            $assignment->setUser($agent);
            $assignment->setStartTime(clone $hourDateTime);
            
            $endTime = clone $hourDateTime;
            $endTime->modify('+' . round($hoursToAssign * 60) . ' minutes');
            $assignment->setEndTime($endTime);
            
            $assignments[] = $assignment;
            $remainingHours -= $hoursToAssign;
        }
        
        return $assignments;
    }

    /**
     * Zapisuje przypisania do bazy danych
     */
    private function saveAssignments(array $assignments): void
    {
        foreach ($assignments as $assignment) {
            $this->entityManager->persist($assignment);
        }
        $this->entityManager->flush();
    }

    /**
     * Optymalizuje istniejący harmonogram używając algorytmu ILP
     */
    public function optimizeSchedule(int $scheduleId): array
    {
        $schedule = $this->scheduleRepository->find($scheduleId);
        if (!$schedule) {
            throw new \InvalidArgumentException('Schedule not found');
        }

        // Pobierz obecne przypisania
        $currentAssignments = $schedule->getShiftAssignments()->toArray();
        
        // Pobierz predykcje
        $predictions = $this->predictionRepository->findByQueueTypeAndDateRange(
            $schedule->getQueueType()->getId(),
            $schedule->getWeekStartDate(),
            $schedule->getWeekEndDate()
        );

        // Pobierz dostępnych agentów
        $availableAgents = $this->getAvailableAgentsWithEfficiency($schedule->getQueueType()->getId());

        // Wykonaj optymalizację ILP
        $optimizedAssignments = $this->performILPOptimization(
            $schedule,
            $predictions,
            $availableAgents,
            $currentAssignments
        );

        // Zastąp obecne przypisania zoptymalizowanymi
        $this->replaceAssignments($schedule, $optimizedAssignments);

        return [
            'scheduleId' => $scheduleId,
            'optimizedAssignmentsCount' => count($optimizedAssignments),
            'totalOptimizedHours' => $schedule->getTotalAssignedHours(),
            'message' => 'Harmonogram został zoptymalizowany'
        ];
    }

    /**
     * Wykonuje optymalizację ILP
     */
    private function performILPOptimization(
        Schedule $schedule,
        array $predictions,
        array $availableAgents,
        array $currentAssignments
    ): array {
        // Implementacja uproszczonego algorytmu ILP
        // W rzeczywistej implementacji można użyć biblioteki jak GLPK lub CBC
        
        $optimizedAssignments = [];
        $hourlyPredictions = $this->groupPredictionsByHour($predictions);
        
        // Dla każdej godziny z predykcją
        foreach ($hourlyPredictions as $hourKey => $expectedCalls) {
            $hourDateTime = \DateTime::createFromFormat('Y-m-d H:i', $hourKey);
            
            // Znajdź obecne przypisania dla tej godziny
            $currentHourAssignments = array_filter($currentAssignments, function($assignment) use ($hourDateTime) {
                return $assignment->getStartTime()->format('Y-m-d H:i') === $hourDateTime->format('Y-m-d H:i');
            });
            
            // Oblicz wymagane godziny
            $requiredHours = $this->calculateRequiredHours($expectedCalls, $availableAgents);
            
            // Optymalizuj przypisania dla tej godziny
            $optimizedHourAssignments = $this->optimizeHourAssignments(
                $schedule,
                $hourDateTime,
                $availableAgents,
                $requiredHours,
                $currentHourAssignments
            );
            
            $optimizedAssignments = array_merge($optimizedAssignments, $optimizedHourAssignments);
        }
        
        return $optimizedAssignments;
    }

    /**
     * Optymalizuje przypisania dla konkretnej godziny
     */
    private function optimizeHourAssignments(
        Schedule $schedule,
        \DateTimeInterface $hourDateTime,
        array $availableAgents,
        float $requiredHours,
        array $currentAssignments
    ): array {
        $optimizedAssignments = [];
        
        // Sortuj agentów według efektywności (najlepsi pierwsi)
        usort($availableAgents, function($a, $b) {
            return $b['efficiencyScore'] <=> $a['efficiencyScore'];
        });
        
        $remainingHours = $requiredHours;
        $maxHoursPerAgent = 1.0;
        
        foreach ($availableAgents as $agentData) {
            if ($remainingHours <= 0) {
                break;
            }
            
            $agent = $agentData['user'];
            $hoursToAssign = min($maxHoursPerAgent, $remainingHours);
            
            // Sprawdź czy agent już ma przypisanie w tej godzinie
            $existingAssignment = array_filter($currentAssignments, function($assignment) use ($agent, $hourDateTime) {
                return $assignment->getUser()->getId() === $agent->getId() &&
                       $assignment->getStartTime()->format('Y-m-d H:i') === $hourDateTime->format('Y-m-d H:i');
            });
            
            if (!empty($existingAssignment)) {
                // Użyj istniejącego przypisania
                $optimizedAssignments[] = reset($existingAssignment);
            } else {
                // Utwórz nowe przypisanie
                $assignment = new ScheduleShiftAssignment();
                $assignment->setSchedule($schedule);
                $assignment->setUser($agent);
                $assignment->setStartTime(clone $hourDateTime);
                
                $endTime = clone $hourDateTime;
                $endTime->modify('+' . round($hoursToAssign * 60) . ' minutes');
                $assignment->setEndTime($endTime);
                
                $optimizedAssignments[] = $assignment;
            }
            
            $remainingHours -= $hoursToAssign;
        }
        
        return $optimizedAssignments;
    }

    /**
     * Zastępuje obecne przypisania nowymi
     */
    private function replaceAssignments(Schedule $schedule, array $newAssignments): void
    {
        // Usuń obecne przypisania
        $this->clearExistingAssignments($schedule);
        
        // Dodaj nowe przypisania
        $this->saveAssignments($newAssignments);
    }
} 