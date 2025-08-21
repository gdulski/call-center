<?php

declare(strict_types=1);

namespace App\Service\Agent;

use App\Entity\Schedule;
use App\Entity\ScheduleShiftAssignment;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Repository\ScheduleShiftAssignmentRepository;
use App\Repository\AgentQueueTypeRepository;
use App\DTO\Agent\AgentReassignmentResponse;
use App\DTO\Agent\AgentReassignmentPreviewResponse;
use App\DTO\Agent\AgentReassignmentChange;
use App\DTO\Agent\AgentInfo;
use App\DTO\Agent\UnresolvedConflict;
use App\DTO\Agent\AgentReplacementInfo;
use App\Enum\UserRole;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Serwis do obsługi przypisania zastępczego agentów
 */
final readonly class AgentReassignmentService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserRepository $userRepository,
        private ScheduleShiftAssignmentRepository $assignmentRepository,
        private AgentQueueTypeRepository $agentQueueTypeRepository,
        private LoggerInterface $logger
    ) {}

    /**
     * Przeprowadza reassignment agenta w harmonogramie
     */
    public function reassignAgent(Schedule $schedule, int $agentId, array $newAvailability): AgentReassignmentResponse
    {
        $this->logger->info('Rozpoczęcie reassignment agenta', [
            'scheduleId' => $schedule->getId(),
            'agentId' => $agentId,
            'newAvailability' => $newAvailability
        ]);

        try {
            // 1. Znajdź wszystkie przypisania danego agenta
            $agentAssignments = $this->findAgentAssignments($schedule, $agentId);
            
            // 2. Sprawdź konflikty z nową dostępnością
            $conflictingAssignments = $this->findConflicts($agentAssignments, $newAvailability);
            
            $changes = [];
            $unresolvedConflicts = [];
            
            // 3. Dla każdego konfliktu znajdź zastępcę
            foreach ($conflictingAssignments as $assignment) {
                $replacementAgent = $this->findReplacementAgent($assignment, $agentId, $schedule);
                
                if ($replacementAgent) {
                    $oldAgent = $assignment->getUser();
                    $this->reassignAssignment($assignment, $replacementAgent);
                    
                    $changes[] = new AgentReassignmentChange(
                        assignmentId: $assignment->getId(),
                        oldAgent: new AgentInfo(
                            id: $oldAgent->getId(),
                            name: $oldAgent->getName()
                        ),
                        newAgent: new AgentInfo(
                            id: $replacementAgent->getId(),
                            name: $replacementAgent->getName()
                        ),
                        date: $assignment->getStartTime()->format('Y-m-d'),
                        time: $assignment->getStartTime()->format('H:i') . '-' . $assignment->getEndTime()->format('H:i'),
                        duration: $assignment->getDurationInHours()
                    );
                } else {
                    $unresolvedConflicts[] = new UnresolvedConflict(
                        assignmentId: $assignment->getId(),
                        date: $assignment->getStartTime()->format('Y-m-d'),
                        time: $assignment->getStartTime()->format('H:i') . '-' . $assignment->getEndTime()->format('H:i'),
                        reason: 'Brak dostępnego zastępcy z odpowiednimi umiejętnościami'
                    );
                }
            }
            
            // 4. Zapisz zmiany
            if (!empty($changes)) {
                $this->entityManager->flush();
            }
            
            $this->logger->info('Zakończenie reassignment agenta', [
                'changesCount' => count($changes),
                'unresolvedCount' => count($unresolvedConflicts)
            ]);
            
            return new AgentReassignmentResponse(
                success: true,
                changes: $changes,
                unresolvedConflicts: $unresolvedConflicts,
                message: sprintf(
                    'Pomyślnie zastąpiono %d przypisań. %d konfliktów nierozwiązanych.',
                    count($changes),
                    count($unresolvedConflicts)
                )
            );
            
        } catch (\Exception $e) {
            $this->logger->error('Błąd podczas reassignment agenta', [
                'error' => $e->getMessage(),
                'scheduleId' => $schedule->getId(),
                'agentId' => $agentId
            ]);
            
            throw $e;
        }
    }

    /**
     * Generuje preview zmian bez wprowadzania ich
     */
    public function generateReassignmentPreview(Schedule $schedule, int $agentId, array $newAvailability): array
    {
        $agentAssignments = $this->findAgentAssignments($schedule, $agentId);
        $conflictingAssignments = $this->findConflicts($agentAssignments, $newAvailability);
        
        $preview = [];
        
        foreach ($conflictingAssignments as $assignment) {
            $replacementAgent = $this->findReplacementAgent($assignment, $agentId, $schedule);
            
            $preview[] = new AgentReassignmentPreviewResponse(
                assignmentId: $assignment->getId(),
                currentAgent: new AgentInfo(
                    id: $assignment->getUser()->getId(),
                    name: $assignment->getUser()->getName()
                ),
                suggestedReplacement: $replacementAgent ? new AgentReplacementInfo(
                    id: $replacementAgent->getId(),
                    name: $replacementAgent->getName(),
                    efficiencyScore: $this->getAgentEfficiencyScore($replacementAgent, $schedule)
                ) : null,
                date: $assignment->getStartTime()->format('Y-m-d'),
                time: $assignment->getStartTime()->format('H:i') . '-' . $assignment->getEndTime()->format('H:i'),
                duration: $assignment->getDurationInHours(),
                canBeReplaced: $replacementAgent !== null
            );
        }
        
        return $preview;
    }

    /**
     * Znajduje wszystkie przypisania danego agenta w harmonogramie
     */
    private function findAgentAssignments(Schedule $schedule, int $agentId): array
    {
        return $this->assignmentRepository->findBy([
            'schedule' => $schedule,
            'user' => $agentId
        ]);
    }

    /**
     * Sprawdza konflikty między przypisaniami a nową dostępnością
     */
    private function findConflicts(array $assignments, array $newAvailability): array
    {
        $newStartTime = new \DateTime($newAvailability['startTime']);
        $newEndTime = new \DateTime($newAvailability['endTime']);
        
        $conflicts = [];
        
        foreach ($assignments as $assignment) {
            $assignmentStart = $assignment->getStartTime();
            $assignmentEnd = $assignment->getEndTime();
            
            // Sprawdź czy przypisanie koliduje z nową dostępnością
            if ($this->timeRangesOverlap($assignmentStart, $assignmentEnd, $newStartTime, $newEndTime)) {
                $conflicts[] = $assignment;
            }
        }
        
        return $conflicts;
    }

    /**
     * Sprawdza czy dwa zakresy czasowe się nakładają
     */
    private function timeRangesOverlap(\DateTime $start1, \DateTime $end1, \DateTime $start2, \DateTime $end2): bool
    {
        return $start1 < $end2 && $start2 < $end1;
    }

    /**
     * Znajduje zastępcę dla danego przypisania
     */
    private function findReplacementAgent(ScheduleShiftAssignment $assignment, int $excludedAgentId, Schedule $schedule): ?User
    {
        $queueType = $schedule->getQueueType();
        $assignmentStart = $assignment->getStartTime();
        $assignmentEnd = $assignment->getEndTime();
        
        // Znajdź agentów z odpowiednimi umiejętnościami dla tej kolejki
        $qualifiedAgents = $this->agentQueueTypeRepository->findBy(['queueType' => $queueType]);
        $qualifiedAgentIds = array_map(fn($aqt) => $aqt->getUser()->getId(), $qualifiedAgents);
        
        // Wyklucz zmieniającego agenta
        $qualifiedAgentIds = array_filter($qualifiedAgentIds, fn($id) => $id !== $excludedAgentId);
        
        if (empty($qualifiedAgentIds)) {
            return null;
        }
        
        // Znajdź dostępnych agentów w tym czasie
        $availableAgents = $this->findAvailableAgentsInTimeRange(
            $qualifiedAgentIds,
            $assignmentStart,
            $assignmentEnd,
            $schedule
        );
        
        if (empty($availableAgents)) {
            return null;
        }
        
        // Sortuj według efektywności (najlepsi pierwsi)
        usort($availableAgents, function($a, $b) use ($queueType) {
            $efficiencyA = $this->getAgentEfficiencyScore($a, $queueType);
            $efficiencyB = $this->getAgentEfficiencyScore($b, $queueType);
            return $efficiencyB <=> $efficiencyA;
        });
        
        return $availableAgents[0];
    }

    /**
     * Znajduje dostępnych agentów w danym zakresie czasowym
     */
    private function findAvailableAgentsInTimeRange(array $agentIds, \DateTime $startTime, \DateTime $endTime, Schedule $schedule): array
    {
        $allAgents = $this->userRepository->findAgentsByIds($agentIds);
        $availableAgents = [];
        
        foreach ($allAgents as $agent) {
            // Sprawdź czy agent ma inne przypisania w tym czasie
            $conflictingAssignments = $this->assignmentRepository->findConflictingAssignments(
                $agent->getId(),
                $startTime,
                $endTime,
                $schedule
            );
            
            if (empty($conflictingAssignments)) {
                $availableAgents[] = $agent;
            }
        }
        
        return $availableAgents;
    }

    /**
     * Przypisuje nowego agenta do przypisania
     */
    private function reassignAssignment(ScheduleShiftAssignment $assignment, User $newAgent): void
    {
        $assignment->setUser($newAgent);
        $this->entityManager->persist($assignment);
    }

    /**
     * Pobiera score efektywności agenta dla danej kolejki
     */
    private function getAgentEfficiencyScore(User $agent, $queueType): float
    {
        $agentQueueType = $this->agentQueueTypeRepository->findOneBy([
            'user' => $agent,
            'queueType' => $queueType
        ]);
        
        return $agentQueueType ? $agentQueueType->getEfficiencyScore() : 0.0;
    }
}
