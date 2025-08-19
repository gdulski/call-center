<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Schedule;
use App\Entity\QueueType;
use App\Enum\ScheduleStatus;
use App\Exception\ScheduleValidationException;
use App\Repository\ScheduleRepository;
use App\Repository\QueueTypeRepository;
use App\Service\AgentReassignmentService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

final readonly class ScheduleService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduleRepository $scheduleRepository,
        private QueueTypeRepository $queueTypeRepository,
        private ScheduleGenerationService $scheduleGenerationService,
        private ILPOptimizationService $ilpOptimizationService,
        private AgentReassignmentService $agentReassignmentService,
        private LoggerInterface $logger
    ) {}

    public function createSchedule(int $queueTypeId, string $weekStartDate, string $optimizationType): array
    {
        try {
            $this->entityManager->beginTransaction();

            $queueType = $this->queueTypeRepository->find($queueTypeId);
            $weekStartDateTime = new \DateTime($weekStartDate);

            // Create new schedule
            $schedule = new Schedule();
            $schedule->setQueueType($queueType);
            $schedule->setWeekStartDate($weekStartDateTime);
            $schedule->setStatus(ScheduleStatus::DRAFT);

            $this->entityManager->persist($schedule);
            $this->entityManager->flush();

            // Generate assignments
            $generationResult = $this->scheduleGenerationService->generateSchedule($schedule->getId());

            // Perform optimization based on type
            $optimizationResult = $this->performOptimization($schedule, $optimizationType);

            $this->entityManager->commit();

            return [
                'id' => $schedule->getId(),
                'queueType' => $queueType->getName(),
                'weekStartDate' => $schedule->getWeekStartDate()->format('Y-m-d'),
                'weekEndDate' => $schedule->getWeekEndDate()->format('Y-m-d'),
                'status' => $schedule->getStatus()->value,
                'optimizationType' => $optimizationType,
                'generationResult' => $generationResult,
                'optimizationResult' => $optimizationResult
            ];

        } catch (\Exception $e) {
            $this->entityManager->rollback();
            $this->logger->error('Schedule creation failed', ['error' => $e->getTraceAsString()]);
            throw new ScheduleValidationException(['Failed to create schedule: ' . $e->getMessage()]);
        }
    }

    public function findScheduleById(int $id): ?Schedule
    {
        return $this->scheduleRepository->findWithRelations($id);
    }

    public function findAllOrderedByWeekStartDate(): array
    {
        $schedules = $this->scheduleRepository->findAllOrderedByWeekStartDate();
        
        $data = [];
        foreach ($schedules as $schedule) {
            $data[] = [
                'id' => $schedule->getId(),
                'queueType' => [
                    'id' => $schedule->getQueueType()->getId(),
                    'name' => $schedule->getQueueType()->getName()
                ],
                'weekStartDate' => $schedule->getWeekStartDate()->format('Y-m-d'),
                'weekEndDate' => $schedule->getWeekEndDate()->format('Y-m-d'),
                'weekIdentifier' => $schedule->getWeekIdentifier(),
                'status' => $schedule->getStatus()->value,
                'totalAssignedHours' => $schedule->getTotalAssignedHours(),
                'assignmentsCount' => $schedule->getShiftAssignments()->count()
            ];
        }
        
        return $data;
    }

    public function getScheduleDetails(Schedule $schedule): array
    {
        $assignments = [];
        foreach ($schedule->getShiftAssignments() as $assignment) {
            $assignments[] = [
                'id' => $assignment->getId(),
                'agentId' => $assignment->getUser()->getId(),
                'agentName' => $assignment->getUser()->getName(),
                'startTime' => $assignment->getStartTime()->format('Y-m-d\TH:i:sP'),
                'endTime' => $assignment->getEndTime()->format('Y-m-d\TH:i:sP'),
                'duration' => $assignment->getDurationInHours()
            ];
        }
        
        return [
            'id' => $schedule->getId(),
            'queueType' => [
                'id' => $schedule->getQueueType()->getId(),
                'name' => $schedule->getQueueType()->getName()
            ],
            'weekStartDate' => $schedule->getWeekStartDate()->format('Y-m-d'),
            'weekEndDate' => $schedule->getWeekEndDate()->format('Y-m-d'),
            'status' => $schedule->getStatus()->value,
            'totalAssignedHours' => $schedule->getTotalAssignedHours(),
            'assignments' => $assignments
        ];
    }

    public function generateSchedule(int $id): array
    {
        try {
            $result = $this->scheduleGenerationService->generateSchedule($id);
            return $result;
        } catch (\InvalidArgumentException $e) {
            throw new ScheduleValidationException([$e->getMessage()]);
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to generate schedule: ' . $e->getMessage()]);
        }
    }

    public function optimizeSchedule(int $id): array
    {
        try {
            $result = $this->scheduleGenerationService->optimizeSchedule($id);
            return $result;
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to optimize schedule: ' . $e->getMessage()]);
        }
    }

    public function optimizeILP(int $id): array
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            // Remove existing assignments
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('App\Entity\ScheduleShiftAssignment', 'ssa')
                ->where('ssa.schedule = :schedule')
                ->setParameter('schedule', $schedule);
            $qb->getQuery()->execute();
            
            // Perform ILP optimization
            $optimizedAssignments = $this->ilpOptimizationService->optimizeScheduleILP($schedule);
            
            // Save new assignments
            foreach ($optimizedAssignments as $assignment) {
                $this->entityManager->persist($assignment);
            }
            $this->entityManager->flush();
            
            // Calculate metrics
            $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
            $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
            
            return [
                'assignmentsCount' => count($optimizedAssignments),
                'totalHours' => $metrics['totalHours'],
                'metrics' => $metrics,
                'validation' => $validation
            ];
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to optimize schedule with ILP: ' . $e->getMessage()]);
        }
    }

    public function getScheduleMetrics(int $id): array
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
            $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
            
            return [
                'metrics' => $metrics,
                'validation' => $validation
            ];
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to get schedule metrics: ' . $e->getMessage()]);
        }
    }

    public function updateStatus(int $id, string $status): array
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            $newStatus = ScheduleStatus::from($status);
            $schedule->setStatus($newStatus);
            
            $this->entityManager->flush();
            
            return [
                'id' => $schedule->getId(),
                'status' => $schedule->getStatus()->value
            ];
        } catch (\ValueError $e) {
            throw new ScheduleValidationException(['Invalid status']);
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to update schedule status: ' . $e->getMessage()]);
        }
    }

    public function deleteSchedule(int $id): void
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            $this->entityManager->remove($schedule);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to delete schedule: ' . $e->getMessage()]);
        }
    }

    public function getReassignmentPreview(int $id, int $agentId, array $newAvailability): array
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            $preview = $this->agentReassignmentService->generateReassignmentPreview(
                $schedule,
                $agentId,
                $newAvailability
            );
            
            return $preview;
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to generate reassignment preview: ' . $e->getMessage()]);
        }
    }

    public function reassignAgent(int $id, int $agentId, array $newAvailability): array
    {
        try {
            $schedule = $this->findScheduleById($id);
            if (!$schedule) {
                throw new ScheduleValidationException(['Schedule not found']);
            }

            $result = $this->agentReassignmentService->reassignAgent(
                $schedule,
                $agentId,
                $newAvailability
            );
            
            return $result;
        } catch (\Exception $e) {
            throw new ScheduleValidationException(['Failed to reassign agent: ' . $e->getMessage()]);
        }
    }

    private function performOptimization(Schedule $schedule, string $optimizationType): array
    {
        if ($optimizationType === 'ilp') {
            // Remove existing assignments before ILP optimization
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('App\Entity\ScheduleShiftAssignment', 'ssa')
                ->where('ssa.schedule = :schedule')
                ->setParameter('schedule', $schedule);
            $qb->getQuery()->execute();

            // Perform ILP optimization
            $optimizedAssignments = $this->ilpOptimizationService->optimizeScheduleILP($schedule);
            
            // Save new assignments
            foreach ($optimizedAssignments as $assignment) {
                $this->entityManager->persist($assignment);
            }
            $this->entityManager->flush();

            // Calculate metrics
            $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
            $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
            
            return [
                'type' => 'ilp',
                'assignmentsCount' => count($optimizedAssignments),
                'totalHours' => $metrics['totalHours'],
                'metrics' => $metrics,
                'validation' => $validation
            ];
        } else {
            // Perform heuristic optimization
            $result = $this->scheduleGenerationService->optimizeSchedule($schedule->getId());
            $result['type'] = 'heuristic';
            return $result;
        }
    }
}
