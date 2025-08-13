<?php

namespace App\Service;

use App\Entity\Schedule;
use App\Entity\ScheduleShiftAssignment;
use App\Entity\User;
use App\Entity\CallQueueVolumePrediction;
use App\Entity\AgentQueueType;
use App\Repository\CallQueueVolumePredictionRepository;
use App\Repository\AgentQueueTypeRepository;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Serwis do zaawansowanej optymalizacji ILP (Integer Linear Programming)
 * dla harmonogramu call center
 */
class ILPOptimizationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CallQueueVolumePredictionRepository $predictionRepository,
        private AgentQueueTypeRepository $agentQueueTypeRepository
    ) {}

    /**
     * Wykonuje zaawansowaną optymalizację ILP dla harmonogramu
     */
    public function optimizeScheduleILP(Schedule $schedule): array
    {
        // Pobierz dane wejściowe
        $predictions = $this->predictionRepository->findByQueueTypeAndDateRange(
            $schedule->getQueueType()->getId(),
            $schedule->getWeekStartDate(),
            $schedule->getWeekEndDate()
        );

        $availableAgents = $this->getAvailableAgentsWithEfficiency($schedule->getQueueType()->getId());
        
        // Dodaj debug logging
        error_log("ILP Debug: Found " . count($predictions) . " predictions");
        error_log("ILP Debug: Found " . count($availableAgents) . " available agents");
        
        // Sprawdź czy są dostępni agenci
        if (empty($availableAgents)) {
            throw new \RuntimeException(
                'Brak dostępnych agentów dla typu kolejki: ' . $schedule->getQueueType()->getName()
            );
        }
        
        // Przygotuj dane dla algorytmu ILP
        $ilpData = $this->prepareILPData($schedule, $predictions, $availableAgents);
        
        // Debug: sprawdź dane wejściowe
        error_log("ILP Debug: Hours count: " . count($ilpData['hours']));
        error_log("ILP Debug: Agents count: " . count($ilpData['agents']));
        error_log("ILP Debug: Demand count: " . count($ilpData['demand']));
        
        // Wykonaj optymalizację
        $optimizedAssignments = $this->solveILP($ilpData);
        
        // Debug: sprawdź wynik
        error_log("ILP Debug: Generated " . count($optimizedAssignments) . " assignments");
        
        return $optimizedAssignments;
    }

    /**
     * Przygotowuje dane dla algorytmu ILP
     */
    private function prepareILPData(Schedule $schedule, array $predictions, array $availableAgents): array
    {
        $hours = [];
        $agents = [];
        $demand = [];
        $efficiency = [];
        
        // Grupuj predykcje według godzin
        foreach ($predictions as $prediction) {
            $hourKey = $prediction->getHour()->format('Y-m-d H:i');
            $hours[] = $hourKey;
            $demand[$hourKey] = $prediction->getExpectedCalls();
        }
        
        // Przygotuj dane agentów
        foreach ($availableAgents as $agentData) {
            $agentId = $agentData['user']->getId();
            $agents[] = $agentId;
            $efficiency[$agentId] = $agentData['efficiencyScore'];
        }
        
        return [
            'hours' => $hours,
            'agents' => $agents,
            'demand' => $demand,
            'efficiency' => $efficiency,
            'schedule' => $schedule
        ];
    }

    /**
     * Rozwiązuje problem ILP
     * Implementacja uproszczonego algorytmu - w rzeczywistości można użyć biblioteki jak GLPK
     */
    private function solveILP(array $ilpData): array
    {
        $assignments = [];
        $hours = $ilpData['hours'];
        $agents = $ilpData['agents'];
        $demand = $ilpData['demand'];
        $efficiency = $ilpData['efficiency'];
        $schedule = $ilpData['schedule'];
        
        // Debug: sprawdź dane wejściowe
        error_log("ILP Debug: solveILP input - hours: " . count($hours) . ", agents: " . count($agents) . ", demand: " . count($demand));
        error_log("ILP Debug: Sample hours: " . implode(', ', array_slice($hours, 0, 3)));
        error_log("ILP Debug: Sample agents: " . implode(', ', array_slice($agents, 0, 3)));
        
        // Sprawdź czy są dostępni agenci
        if (empty($agents)) {
            error_log("ILP Debug: No agents available");
            return $assignments; // Brak agentów - zwróć pustą listę
        }
        
        // Sprawdź czy efektywność nie jest zerowa
        $totalEfficiency = array_sum($efficiency);
        if ($totalEfficiency <= 0) {
            // Jeśli efektywność jest zerowa, użyj domyślnej wartości
            $avgEfficiency = 1.0; // Domyślna efektywność
        } else {
            $avgEfficiency = $totalEfficiency / count($agents);
        }
        
        // Sortuj agentów według efektywności (malejąco)
        usort($agents, function($a, $b) use ($efficiency) {
            return $efficiency[$b] <=> $efficiency[$a];
        });
        
        // Grupuj godziny według dni
        $dailyDemand = [];
        foreach ($hours as $hourKey) {
            $hourDateTime = \DateTime::createFromFormat('Y-m-d H:i', $hourKey);
            $dayKey = $hourDateTime->format('Y-m-d');
            
            if (!isset($dailyDemand[$dayKey])) {
                $dailyDemand[$dayKey] = 0;
            }
            $dailyDemand[$dayKey] += $demand[$hourKey];
        }
        
        // Dla każdego dnia
        foreach ($dailyDemand as $dayKey => $totalDailyCalls) {
            if ($totalDailyCalls <= 0) {
                continue;
            }
            
            // Debug: sprawdź dane dzienne
            error_log("ILP Debug: Processing day $dayKey with $totalDailyCalls calls");
            
            // Oblicz wymagane godziny pracy na dzień
            $callsPerHourPerAgent = 10 * $avgEfficiency; // 10 połączeń na godzinę jako baseline
            
            // Sprawdź czy nie dzielimy przez zero
            if ($callsPerHourPerAgent <= 0) {
                $callsPerHourPerAgent = 1; // Domyślna wartość
            }
            
            $requiredHoursPerDay = $totalDailyCalls / $callsPerHourPerAgent;
            
            // Debug: sprawdź obliczenia
            error_log("ILP Debug: Day $dayKey - callsPerHourPerAgent: $callsPerHourPerAgent, requiredHoursPerDay: $requiredHoursPerDay");
            
            // Przydziel agentów na cały dzień - poprawiona logika
            $assignedHours = 0;
            $maxHoursPerAgent = 8.0; // Pełna zmiana - 8 godzin
            $minHoursPerAgent = 4.0; // Minimalna zmiana - 4 godziny
            $agentsUsed = 0;
            
            foreach ($agents as $agentId) {
                // Sprawdź czy agent ma dostępność w tym dniu
                $agentAvailability = $this->getAgentAvailabilityForDay($agentId, $dayKey);
                if ($agentAvailability <= 0) {
                    continue; // Pomiń agenta bez dostępności
                }
                
                $agentEfficiency = $efficiency[$agentId];
                
                // Oblicz godziny do przypisania - lepsze rozłożenie
                $remainingHours = $requiredHoursPerDay - $assignedHours;
                $agentMaxHours = min($maxHoursPerAgent, $agentAvailability);
                
                if ($remainingHours > 0 && $agentMaxHours >= $minHoursPerAgent) {
                    // Przypisz agentowi maksymalnie dostępne godziny lub wymagane
                    $hoursToAssign = min($agentMaxHours, $remainingHours);
                    
                    // Debug: sprawdź przypisania
                    error_log("ILP Debug: Agent $agentId - efficiency: $agentEfficiency, hoursToAssign: $hoursToAssign, agentAvailability: $agentAvailability");
                    
                    // Utwórz przypisanie na cały dzień
                    $assignment = new ScheduleShiftAssignment();
                    $assignment->setSchedule($schedule);
                    
                    // Pobierz obiekt User
                    $user = $this->entityManager->getReference(User::class, $agentId);
                    $assignment->setUser($user);
                    
                    // Ustaw początek na 9:00 danego dnia
                    $startTime = \DateTime::createFromFormat('Y-m-d H:i', $dayKey . ' 09:00');
                    $assignment->setStartTime($startTime);
                    
                    // Ustaw koniec na podstawie przypisanych godzin
                    $endTime = clone $startTime;
                    $endTime->modify('+' . round($hoursToAssign * 60) . ' minutes');
                    $assignment->setEndTime($endTime);
                    
                    $assignments[] = $assignment;
                    $assignedHours += $hoursToAssign;
                    $agentsUsed++;
                    
                    // Debug: potwierdź utworzenie przypisania
                    error_log("ILP Debug: Created assignment for agent $agentId on $dayKey: {$startTime->format('H:i')} - {$endTime->format('H:i')} ({$hoursToAssign}h)");
                    
                    // Jeśli pokryliśmy zapotrzebowanie, zakończ
                    if ($assignedHours >= $requiredHoursPerDay) {
                        break;
                    }
                }
            }
            
            // Debug: podsumowanie dnia
            error_log("ILP Debug: Day $dayKey completed - assignedHours: $assignedHours, requiredHours: $requiredHoursPerDay, agentsUsed: $agentsUsed");
        }
        
        return $assignments;
    }

    /**
     * Pobiera dostępnych agentów z ich efektywnością
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
        
        // Dodaj logowanie dla diagnostyki
        if (empty($agents)) {
            // Sprawdź czy w ogóle są agenci w systemie
            $allAgents = $this->entityManager->getRepository(User::class)->findBy(['role' => UserRole::AGENT]);
            $totalAgents = count($allAgents);
            
            // Sprawdź czy są przypisania do typów kolejek
            $allAssignments = $this->entityManager->getRepository(AgentQueueType::class)->findAll();
            $totalAssignments = count($allAssignments);
            
            throw new \RuntimeException(
                "Brak agentów dla typu kolejki ID: $queueTypeId. " .
                "Łącznie agentów w systemie: $totalAgents, " .
                "Łącznie przypisań do typów kolejek: $totalAssignments"
            );
        }
        
        return $agents;
    }

    /**
     * Sprawdza dostępność agenta w konkretnym dniu
     */
    private function getAgentAvailabilityForDay(int $agentId, string $dayKey): float
    {
        $startDate = \DateTime::createFromFormat('Y-m-d', $dayKey)->setTime(0, 0, 0);
        $endDate = (clone $startDate)->setTime(23, 59, 59);
        
        $qb = $this->entityManager->createQueryBuilder();
        $qb->select('aa.startDate, aa.endDate')
           ->from('App\Entity\AgentAvailability', 'aa')
           ->where('aa.agent = :agentId')
           ->andWhere('aa.startDate >= :startDate')
           ->andWhere('aa.endDate <= :endDate')
           ->setParameter('agentId', $agentId)
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);
        
        $results = $qb->getQuery()->getResult();
        
        $totalHours = 0.0;
        foreach ($results as $result) {
            $start = $result['startDate'];
            $end = $result['endDate'];
            
            // Oblicz różnicę w godzinach
            $diff = $end->diff($start);
            $hours = $diff->h + ($diff->i / 60.0) + ($diff->s / 3600.0);
            $totalHours += $hours;
        }
        
        return $totalHours;
    }

    /**
     * Oblicza metryki jakości harmonogramu
     */
    public function calculateScheduleMetrics(Schedule $schedule): array
    {
        $assignments = $schedule->getShiftAssignments()->toArray();
        
        $totalHours = 0;
        $agentHours = [];
        $hourlyCoverage = [];
        
        foreach ($assignments as $assignment) {
            $agentId = $assignment->getUser()->getId();
            $hours = $assignment->getDurationInHours();
            $startHour = $assignment->getStartTime()->format('Y-m-d H:i');
            
            $totalHours += $hours;
            
            if (!isset($agentHours[$agentId])) {
                $agentHours[$agentId] = 0;
            }
            $agentHours[$agentId] += $hours;
            
            if (!isset($hourlyCoverage[$startHour])) {
                $hourlyCoverage[$startHour] = 0;
            }
            $hourlyCoverage[$startHour] += $hours;
        }
        
        return [
            'totalHours' => $totalHours,
            'agentCount' => count($agentHours),
            'averageHoursPerAgent' => count($agentHours) > 0 ? $totalHours / count($agentHours) : 0,
            'maxHoursPerAgent' => count($agentHours) > 0 ? max($agentHours) : 0,
            'minHoursPerAgent' => count($agentHours) > 0 ? min($agentHours) : 0,
            'hourlyCoverage' => $hourlyCoverage
        ];
    }

    /**
     * Sprawdza czy harmonogram spełnia ograniczenia
     */
    public function validateScheduleConstraints(Schedule $schedule): array
    {
        $assignments = $schedule->getShiftAssignments()->toArray();
        $violations = [];
        
        // Sprawdź maksymalne godziny pracy na agenta (np. 40h/tydzień)
        $maxWeeklyHours = 40;
        $agentWeeklyHours = [];
        
        foreach ($assignments as $assignment) {
            $agentId = $assignment->getUser()->getId();
            $hours = $assignment->getDurationInHours();
            
            if (!isset($agentWeeklyHours[$agentId])) {
                $agentWeeklyHours[$agentId] = 0;
            }
            $agentWeeklyHours[$agentId] += $hours;
        }
        
        foreach ($agentWeeklyHours as $agentId => $hours) {
            if ($hours > $maxWeeklyHours) {
                $violations[] = "Agent $agentId przekroczył limit godzin: $hours/$maxWeeklyHours";
            }
        }
        
        // Sprawdź nakładające się przypisania dla tego samego agenta
        $agentAssignments = [];
        foreach ($assignments as $assignment) {
            $agentId = $assignment->getUser()->getId();
            if (!isset($agentAssignments[$agentId])) {
                $agentAssignments[$agentId] = [];
            }
            $agentAssignments[$agentId][] = $assignment;
        }
        
        foreach ($agentAssignments as $agentId => $agentAssignmentsList) {
            for ($i = 0; $i < count($agentAssignmentsList); $i++) {
                for ($j = $i + 1; $j < count($agentAssignmentsList); $j++) {
                    $assignment1 = $agentAssignmentsList[$i];
                    $assignment2 = $agentAssignmentsList[$j];
                    
                    if ($this->assignmentsOverlap($assignment1, $assignment2)) {
                        $violations[] = "Agent $agentId ma nakładające się przypisania: " . 
                                       $assignment1->getTimeRange() . " i " . $assignment2->getTimeRange();
                    }
                }
            }
        }
        
        return [
            'isValid' => empty($violations),
            'violations' => $violations,
            'totalViolations' => count($violations)
        ];
    }

    /**
     * Sprawdza czy dwa przypisania się nakładają
     */
    private function assignmentsOverlap(ScheduleShiftAssignment $a1, ScheduleShiftAssignment $a2): bool
    {
        return $a1->getStartTime() < $a2->getEndTime() && $a2->getStartTime() < $a1->getEndTime();
    }
} 