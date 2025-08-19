<?php

declare(strict_types=1);

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

/**
 * Serwis do obsługi przypisania zastępczego agentów
 */
final readonly class ScheduleGenerationService
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

        // $this->logger->info('API Debug', [
        //     array_map(fn($agent) => $agent['user']->getId(), $availableAgents),
        //     $weekStartDate,
        //     $weekEndDate
        // ]);

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

        // $this->logger->info('API Debug', ['hourlyAvailabilities' => $hourlyAvailabilities]);
        // $this->logger->info('API Debug', ['hourlyPredictions' => $hourlyPredictions]);

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
        $this->scheduleRepository->clearShiftAssignments($schedule);
    }

    /**
     * Pobiera dostępnych agentów z ich efektywnością dla danej kolejki
     */
    private function getAvailableAgentsWithEfficiency(int $queueTypeId): array
    {
        return $this->userRepository->findAgentsWithEfficiencyByQueueType($queueTypeId);
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
                
                // Dodaj agenta tylko jeśli nie jest już na liście
                if (!in_array($agentId, $grouped[$hourKey])) {
                    $grouped[$hourKey][] = $agentId;
                }
                
                $current->modify('+1 hour');
            }
        }
        
        // Sortuj klucze chronologicznie
        ksort($grouped);
        
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

        // Grupuj predykcje według dni zamiast godzin dla lepszego planowania
        $dailyPredictions = [];
        foreach ($hourlyPredictions as $hourKey => $expectedCalls) {
            $hourDateTime = \DateTime::createFromFormat('Y-m-d H:i', $hourKey);
            $dayKey = $hourDateTime->format('Y-m-d');
            
            if (!isset($dailyPredictions[$dayKey])) {
                $dailyPredictions[$dayKey] = 0;
            }
            $dailyPredictions[$dayKey] += $expectedCalls;
        }

        // Dla każdego dnia
        foreach ($dailyPredictions as $dayKey => $totalDailyCalls) {
            if ($totalDailyCalls <= 0) {
                continue;
            }

            // Znajdź dostępnych agentów dla tego dnia
            $dayAvailableAgents = [];
            foreach ($availableAgents as $agentData) {
                $agentId = $agentData['user']->getId();
                
                // Sprawdź dostępność agenta w tym dniu
                $dayAvailability = $this->getAgentDayAvailability($agentId, $dayKey, $hourlyAvailabilities);
                if ($dayAvailability > 0) {
                    $dayAvailableAgents[] = [
                        'user' => $agentData['user'],
                        'efficiencyScore' => $agentData['efficiencyScore'],
                        'availability' => $dayAvailability
                    ];
                }
            }

            if (empty($dayAvailableAgents)) {
                continue; // Brak dostępnych agentów w tym dniu
            }

            // Oblicz wymagane godziny pracy na dzień
            $requiredHours = $this->calculateRequiredHours($totalDailyCalls, $dayAvailableAgents);
            
            // Przydziel agentów na cały dzień
            $dayAssignments = $this->assignAgentsToDay(
                $schedule,
                $dayKey,
                $dayAvailableAgents,
                $requiredHours
            );
            
            $assignments = array_merge($assignments, $dayAssignments);
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

        // Oblicz minimalne wymagane godziny
        $minRequiredHours = $expectedCalls / $callsPerHourPerAgent;
        
        // Zapewnij minimalny czas pracy - przynajmniej 4 godziny dla lepszego wykorzystania agentów
        $minShiftHours = 4.0;
        
        // Jeśli wymagane godziny są mniejsze niż minimalna zmiana, użyj minimalnej zmiany
        // To zapewni lepsze wykorzystanie dostępności agentów
        return max($minRequiredHours, $minShiftHours);
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
        
        // Zwiększamy maksymalną liczbę godzin na agenta - pozwalamy na dłuższe zmiany
        $maxHoursPerAgent = 8.0; // Pełna zmiana - 8 godzin
        $minHoursPerAgent = 4.0; // Minimalna zmiana - 4 godziny
        
        // Sortuj agentów według efektywności (najlepsi pierwsi)
        usort($availableAgents, function($a, $b) {
            return $b['efficiencyScore'] <=> $a['efficiencyScore'];
        });
        
        foreach ($availableAgents as $agentData) {
            if ($remainingHours <= 0) {
                break;
            }
            
            $agent = $agentData['user'];
            $efficiency = $agentData['efficiencyScore'];
            
            // Oblicz godziny do przydzielenia dla tego agenta
            // Daj agentowi więcej godzin jeśli to możliwe, ale nie mniej niż minimum
            $hoursToAssign = min($maxHoursPerAgent, $remainingHours);
            
            // Jeśli zostało mniej niż minimum, ale więcej niż 0, daj minimum
            if ($hoursToAssign < $minHoursPerAgent && $remainingHours >= $minHoursPerAgent) {
                $hoursToAssign = $minHoursPerAgent;
            }
            
            // Jeśli zostało mniej niż minimum i to ostatni agent, daj wszystko co zostało
            if ($hoursToAssign < $minHoursPerAgent && $remainingHours < $minHoursPerAgent) {
                $hoursToAssign = $remainingHours;
            }
            
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
     * Sprawdza dostępność agenta w konkretnym dniu
     */
    private function getAgentDayAvailability(int $agentId, string $dayKey, array $hourlyAvailabilities): float
    {
        $totalHours = 0;
        
        // Sprawdź dostępność dla każdej godziny w dniu (od 8:00 do 18:00 - standardowe godziny pracy)
        for ($hour = 8; $hour <= 18; $hour++) {
            $hourKey = $dayKey . ' ' . str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
            
            if (isset($hourlyAvailabilities[$hourKey]) && in_array($agentId, $hourlyAvailabilities[$hourKey])) {
                $totalHours += 1.0; // Agent dostępny przez całą godzinę
            }
        }
        
        // Jeśli nie ma dostępności w hourlyAvailabilities, spróbuj pobrać z bazy danych
        if ($totalHours == 0) {
            $totalHours = $this->getAgentAvailabilityFromDatabase($agentId, $dayKey);
        }
        
        return $totalHours;
    }
    
    /**
     * Pobiera dostępność agenta z bazy danych dla konkretnego dnia
     */
    private function getAgentAvailabilityFromDatabase(int $agentId, string $dayKey): float
    {
        $startDate = new \DateTime($dayKey . ' 00:00:00');
        $endDate = new \DateTime($dayKey . ' 23:59:59');
        
        $results = $this->availabilityRepository->findAgentAvailabilityInDateRange($agentId, $startDate, $endDate);
        
        $totalHours = 0.0;
        foreach ($results as $result) {
            $start = $result['startDate'];
            $end = $result['endDate'];
            
            // Oblicz przecięcie z dniem
            $dayStart = max($start, $startDate);
            $dayEnd = min($end, $endDate);
            
            if ($dayStart < $dayEnd) {
                // Oblicz różnicę w godzinach
                $diff = $dayEnd->diff($dayStart);
                $hours = $diff->h + ($diff->i / 60.0) + ($diff->s / 3600.0);
                $totalHours += $hours;
            }
        }
        
        return $totalHours;
    }

    /**
     * Przydziela agentów do konkretnego dnia
     */
    private function assignAgentsToDay(
        Schedule $schedule,
        string $dayKey,
        array $availableAgents,
        float $requiredHours
    ): array {
        $assignments = [];
        $remainingHours = $requiredHours;
        
        // Sortuj agentów według efektywności (najlepsi pierwsi)
        usort($availableAgents, function($a, $b) {
            return $b['efficiencyScore'] <=> $a['efficiencyScore'];
        });
        
        foreach ($availableAgents as $agentData) {
            if ($remainingHours <= 0) {
                break;
            }
            
            $agent = $agentData['user'];
            $efficiency = $agentData['efficiencyScore'];
            $availability = $agentData['availability']; // Dostępność dla całego dnia
            
            // Oblicz godziny do przydzielenia dla tego agenta
            // Daj agentowi więcej godzin jeśli to możliwe, ale nie mniej niż minimum
            $hoursToAssign = min($availability, $remainingHours);
            
            // Jeśli zostało mniej niż minimum, ale więcej niż 0, daj minimum
            if ($hoursToAssign < 4.0 && $remainingHours >= 4.0) { // Użyj minimalnej zmiany
                $hoursToAssign = 4.0;
            }
            
            // Jeśli zostało mniej niż minimum i to ostatni agent, daj wszystko co zostało
            if ($hoursToAssign < 4.0 && $remainingHours < 4.0) {
                $hoursToAssign = $remainingHours;
            }
            
            // Utwórz przypisanie
            $assignment = new ScheduleShiftAssignment();
            $assignment->setSchedule($schedule);
            $assignment->setUser($agent);
            
            // Ustaw początek na 9:00 danego dnia (standardowa godzina rozpoczęcia pracy)
            $startTime = new \DateTime($dayKey . ' 09:00:00');
            $assignment->setStartTime($startTime);
            
            $endTime = clone $startTime;
            $endTime->modify('+' . round($hoursToAssign * 60) . ' minutes');
            $assignment->setEndTime($endTime);
            
            $assignments[] = $assignment;
            $remainingHours -= $hoursToAssign;
            
            // Jeśli pokryliśmy zapotrzebowanie, zakończ
            if ($remainingHours <= 0) {
                break;
            }
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
     * Optymalizuje istniejący harmonogram używając algorytmu heurystycznego
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

        // Wykonaj optymalizację heurystyczną
        $optimizedAssignments = $this->performHeuristicOptimization(
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
            'message' => 'Harmonogram został zoptymalizowany algorytmem heurystycznym'
        ];
    }

    /**
     * Wykonuje optymalizację heurystyczną
     */
    private function performHeuristicOptimization(
        Schedule $schedule,
        array $predictions,
        array $availableAgents,
        array $currentAssignments
    ): array {
        // Implementacja algorytmu heurystycznego opartego na regułach biznesowych
        // Używa prostych reguł sortowania i przypisywania agentów
        
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