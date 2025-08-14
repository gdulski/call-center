<?php

namespace App\Controller\Api;

use App\Entity\Schedule;
use App\Service\ScheduleGenerationService;
use App\Service\ILPOptimizationService;
use App\Service\AgentReassignmentService;
use App\Repository\ScheduleRepository;
use App\Repository\QueueTypeRepository;
use App\Enum\ScheduleStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Psr\Log\LoggerInterface;

#[Route('/schedules', name: 'api_schedules_')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ScheduleRepository $scheduleRepository,
        private QueueTypeRepository $queueTypeRepository,
        private ScheduleGenerationService $scheduleGenerationService,
        private ILPOptimizationService $ilpOptimizationService,
        private AgentReassignmentService $agentReassignmentService,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private LoggerInterface $logger
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
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
        
        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
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
        
        $data = [
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
        
        return $this->json([
            'success' => true,
            'data' => $data
        ]);
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        

        $data = json_decode($request->getContent(), true);
        
        if (!$data) {
            return $this->json([
                'success' => false,
                'message' => 'Nieprawidłowe dane JSON'
            ], 400);
        }
        
        // Walidacja wymaganych pól
        if (!isset($data['queueTypeId']) || !isset($data['weekStartDate'])) {
            return $this->json([
                'success' => false,
                'message' => 'Brakuje wymaganych pól: queueTypeId, weekStartDate'
            ], 400);
        }
        
        // Sprawdź typ optymalizacji (domyślnie 'ilp')
        $optimizationType = $data['optimizationType'] ?? 'ilp';
        if (!in_array($optimizationType, ['ilp', 'heuristic'])) {
            return $this->json([
                'success' => false,
                'message' => 'Nieprawidłowy typ optymalizacji. Dozwolone wartości: ilp, heuristic'
            ], 400);
        }
        
        $queueType = $this->queueTypeRepository->find($data['queueTypeId']);
        if (!$queueType) {
            return $this->json([
                'success' => false,
                'message' => 'Typ kolejki nie został znaleziony'
            ], 404);
        }
        
        try {
            $weekStartDate = new \DateTime($data['weekStartDate']);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Nieprawidłowy format daty'
            ], 400);
        }
        
        // Sprawdź czy harmonogram już istnieje dla tego typu kolejki i tygodnia
        $existingSchedule = $this->scheduleRepository->findByQueueTypeAndWeek(
            $queueType->getId(),
            $weekStartDate
        );
        
        if ($existingSchedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram dla tego typu kolejki i tygodnia już istnieje'
            ], 409);
        }
        
        try {
            // Rozpocznij transakcję
            $this->entityManager->beginTransaction();
            
            // Utwórz nowy harmonogram
            $schedule = new Schedule();
            $schedule->setQueueType($queueType);
            $schedule->setWeekStartDate($weekStartDate);
            $schedule->setStatus(ScheduleStatus::DRAFT);

            $this->entityManager->persist($schedule);
            $this->entityManager->flush();
            
            // Wygeneruj przypisania w tej samej transakcji
            $generationResult = $this->scheduleGenerationService->generateSchedule($schedule->getId());
            
            // Wykonaj optymalizację na podstawie wybranego typu
            $optimizationResult = null;
            if ($optimizationType === 'ilp') {
                // Wykonaj optymalizację ILP
                $optimizedAssignments = $this->ilpOptimizationService->optimizeScheduleILP($schedule);

                // $this->logger->info('API Debug', ['optimizedAssignments' => $optimizedAssignments]);


                if (count($optimizedAssignments)) {
                    // Usuń istniejące przypisania przed optymalizacją ILP
                    $qb = $this->entityManager->createQueryBuilder();
                    $qb->delete('App\Entity\ScheduleShiftAssignment', 'ssa')
                    ->where('ssa.schedule = :schedule')
                    ->setParameter('schedule', $schedule);
                    $qb->getQuery()->execute();  
                }
                
                // Zapisz nowe przypisania
                foreach ($optimizedAssignments as $assignment) {
                    $this->entityManager->persist($assignment);
                }
                $this->entityManager->flush();

                
                // Oblicz metryki
                $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
                $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
                
                $optimizationResult = [
                    'type' => 'ilp',
                    'assignmentsCount' => count($optimizedAssignments),
                    'totalHours' => $metrics['totalHours'],
                    'metrics' => $metrics,
                    'validation' => $validation
                ];
            } else {
                // Wykonaj optymalizację heurystyczną
                $optimizationResult = $this->scheduleGenerationService->optimizeSchedule($schedule->getId());
                $optimizationResult['type'] = 'heuristic';
            }

            // Zatwierdź transakcję
            $this->entityManager->commit();
            
                                    
            return $this->json([
                'success' => true,
                'message' => 'Harmonogram został utworzony i zoptymalizowany pomyślnie',
                'data' => [
                    'id' => $schedule->getId(),
                    'queueType' => $queueType->getName(),
                    'weekStartDate' => $schedule->getWeekStartDate()->format('Y-m-d'),
                    'weekEndDate' => $schedule->getWeekEndDate()->format('Y-m-d'),
                    'status' => $schedule->getStatus()->value,
                    'optimizationType' => $optimizationType,
                    'generationResult' => $generationResult,
                    'optimizationResult' => $optimizationResult
                ]
            ], 201);
            
        } catch (\Exception $e) {
            
            // Wycofaj transakcję w przypadku błędu
            $this->entityManager->rollback();
            $this->logger->error('API Debug', ['error' => $e->getTraceAsString()]);

            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas tworzenia harmonogramu: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/generate', name: 'generate', methods: ['POST'])]
    public function generate(int $id): JsonResponse
    {
        // Opcjonalny endpoint do ponownego generowania harmonogramu
        // (Harmonogramy są automatycznie generowane podczas tworzenia)
        try {
            $result = $this->scheduleGenerationService->generateSchedule($id);
            
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\InvalidArgumentException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas generowania harmonogramu: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/optimize', name: 'optimize', methods: ['POST'])]
    public function optimize(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        try {
            $result = $this->scheduleGenerationService->optimizeSchedule($id);
            
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas optymalizacji: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/optimize-ilp', name: 'optimize_ilp', methods: ['POST'])]
    public function optimizeILP(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        try {
            // Usuń istniejące przypisania
            $qb = $this->entityManager->createQueryBuilder();
            $qb->delete('App\Entity\ScheduleShiftAssignment', 'ssa')
               ->where('ssa.schedule = :schedule')
               ->setParameter('schedule', $schedule);
            $qb->getQuery()->execute();
            
            // Wykonaj optymalizację ILP
            $optimizedAssignments = $this->ilpOptimizationService->optimizeScheduleILP($schedule);
            
            // Zapisz nowe przypisania
            foreach ($optimizedAssignments as $assignment) {
                $this->entityManager->persist($assignment);
            }
            $this->entityManager->flush();
            
            // Oblicz metryki
            $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
            $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
            
            return $this->json([
                'success' => true,
                'message' => 'Harmonogram został zoptymalizowany używając ILP',
                'data' => [
                    'assignmentsCount' => count($optimizedAssignments),
                    'totalHours' => $metrics['totalHours'],
                    'metrics' => $metrics,
                    'validation' => $validation
                ]
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas optymalizacji ILP: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        $metrics = $this->ilpOptimizationService->calculateScheduleMetrics($schedule);
        $validation = $this->ilpOptimizationService->validateScheduleConstraints($schedule);
        
        return $this->json([
            'success' => true,
            'data' => [
                'metrics' => $metrics,
                'validation' => $validation
            ]
        ]);
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['status'])) {
            return $this->json([
                'success' => false,
                'message' => 'Brakuje pola status'
            ], 400);
        }
        
        try {
            $newStatus = ScheduleStatus::from($data['status']);
            $schedule->setStatus($newStatus);
            
            $this->entityManager->flush();
            
            return $this->json([
                'success' => true,
                'message' => 'Status harmonogramu został zaktualizowany',
                'data' => [
                    'id' => $schedule->getId(),
                    'status' => $schedule->getStatus()->value
                ]
            ]);
        } catch (\ValueError $e) {
            return $this->json([
                'success' => false,
                'message' => 'Nieprawidłowy status'
            ], 400);
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        $this->entityManager->remove($schedule);
        $this->entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Harmonogram został usunięty'
        ]);
    }

    #[Route('/{id}/reassignment-preview', name: 'reassignment_preview', methods: ['POST'])]
    public function reassignmentPreview(int $id, Request $request): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['agentId']) || !isset($data['newAvailability'])) {
            return $this->json([
                'success' => false,
                'message' => 'Brakuje wymaganych pól: agentId, newAvailability'
            ], 400);
        }
        
        try {
            $preview = $this->agentReassignmentService->generateReassignmentPreview(
                $schedule,
                $data['agentId'],
                $data['newAvailability']
            );
            
            return $this->json([
                'success' => true,
                'data' => $preview
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas generowania preview: ' . $e->getMessage()
            ], 500);
        }
    }

    #[Route('/{id}/reassign-agent', name: 'reassign_agent', methods: ['POST'])]
    public function reassignAgent(int $id, Request $request): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json([
                'success' => false,
                'message' => 'Harmonogram nie został znaleziony'
            ], 404);
        }
        
        $data = json_decode($request->getContent(), true);
        
        if (!isset($data['agentId']) || !isset($data['newAvailability'])) {
            return $this->json([
                'success' => false,
                'message' => 'Brakuje wymaganych pól: agentId, newAvailability'
            ], 400);
        }
        
        try {
            $result = $this->agentReassignmentService->reassignAgent(
                $schedule,
                $data['agentId'],
                $data['newAvailability']
            );
            
            return $this->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Wystąpił błąd podczas reassignment: ' . $e->getMessage()
            ], 500);
        }
    }
}