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
        
        // Sprawdź czy są dostępni agenci
        if (empty($availableAgents)) {
            throw new \RuntimeException(
                'Brak dostępnych agentów dla typu kolejki: ' . $schedule->getQueueType()->getName()
            );
        }
        
        // Przygotuj dane dla algorytmu ILP
        $ilpData = $this->prepareILPData($schedule, $predictions, $availableAgents);
        
        // Wykonaj optymalizację
        $optimizedAssignments = $this->solveILP($ilpData);
        
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
        
        // Sprawdź czy są dostępni agenci
        if (empty($agents)) {
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
        
        // Dla każdej godziny
        foreach ($hours as $hourKey) {
            $hourDateTime = \DateTime::createFromFormat('Y-m-d H:i', $hourKey);
            $requiredCalls = $demand[$hourKey];
            
            if ($requiredCalls <= 0) {
                continue;
            }
            
            // Oblicz wymagane godziny pracy
            $callsPerHourPerAgent = 10 * $avgEfficiency; // 10 połączeń na godzinę jako baseline
            
            // Sprawdź czy nie dzielimy przez zero
            if ($callsPerHourPerAgent <= 0) {
                $callsPerHourPerAgent = 1; // Domyślna wartość
            }
            
            $requiredHours = $requiredCalls / $callsPerHourPerAgent;
            
            // Przydziel agentów używając algorytmu zachłannego z priorytetem efektywności
            $assignedHours = 0;
            $maxHoursPerAgent = 1.0;
            
            foreach ($agents as $agentId) {
                if ($assignedHours >= $requiredHours) {
                    break;
                }
                
                $agentEfficiency = $efficiency[$agentId];
                $hoursToAssign = min($maxHoursPerAgent, $requiredHours - $assignedHours);
                
                if ($hoursToAssign > 0) {
                    // Utwórz przypisanie
                    $assignment = new ScheduleShiftAssignment();
                    $assignment->setSchedule($schedule);
                    
                    // Pobierz obiekt User
                    $user = $this->entityManager->getReference(User::class, $agentId);
                    $assignment->setUser($user);
                    
                    $assignment->setStartTime(clone $hourDateTime);
                    
                    $endTime = clone $hourDateTime;
                    $endTime->modify('+' . round($hoursToAssign * 60) . ' minutes');
                    $assignment->setEndTime($endTime);
                    
                    $assignments[] = $assignment;
                    $assignedHours += $hoursToAssign;
                }
            }
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