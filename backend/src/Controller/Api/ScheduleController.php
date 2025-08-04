<?php

namespace App\Controller\Api;

use App\Entity\Schedule;
use App\Repository\ScheduleRepository;
use App\Repository\QueueTypeRepository;
use App\Enum\ScheduleStatus;
use App\Service\ScheduleGenerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/schedules')]
class ScheduleController extends AbstractController
{
    public function __construct(
        private ScheduleRepository $scheduleRepository,
        private QueueTypeRepository $queueTypeRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
        private ScheduleGenerationService $scheduleGenerationService
    ) {}

    #[Route('', name: 'api_schedule_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $queueTypeId = $request->query->get('queueTypeId');
        $weekStartDate = $request->query->get('weekStartDate');
        
        if ($queueTypeId && $weekStartDate) {
            try {
                $date = new \DateTime($weekStartDate);
                $schedule = $this->scheduleRepository->findByQueueTypeAndWeek($queueTypeId, $date);
                $schedules = $schedule ? [$schedule] : [];
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $schedules = $this->scheduleRepository->findAllOrderedByWeekStartDate();
        }
        
        return $this->json($schedules, Response::HTTP_OK, [], [
            'groups' => ['schedule:read']
        ]);
    }

    #[Route('', name: 'api_schedule_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['queueTypeId']) || !isset($data['weekStartDate'])) {
            return $this->json(['error' => 'Queue type ID and week start date are required'], Response::HTTP_BAD_REQUEST);
        }

        $queueType = $this->queueTypeRepository->find($data['queueTypeId']);
        if (!$queueType) {
            return $this->json(['error' => 'Queue type not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $weekStartDate = new \DateTime($data['weekStartDate']);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        // Create a temporary schedule to get the normalized week start date
        $tempSchedule = new Schedule();
        $tempSchedule->setWeekStartDate($weekStartDate);
        $normalizedWeekStartDate = $tempSchedule->getWeekStartDate();
        
        // Check if schedule already exists for this queue type and week identifier
        $weekIdentifier = $normalizedWeekStartDate->format('o-W');
        $existingSchedule = $this->scheduleRepository->findByQueueTypeAndWeekIdentifier($data['queueTypeId'], $weekIdentifier);
        if ($existingSchedule) {
            return $this->json(['error' => 'Schedule already exists for this queue type and week (YYYY-WW format)'], Response::HTTP_CONFLICT);
        }

        $schedule = new Schedule();
        $schedule->setQueueType($queueType);
        $schedule->setWeekStartDate($weekStartDate);
        
        // Ustaw status - jeśli podano string, przekonwertuj na enum
        $status = $data['status'] ?? 'draft';
        if (is_string($status)) {
            $status = ScheduleStatus::from($status);
        }
        $schedule->setStatus($status);

        $errors = $this->validator->validate($schedule);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($schedule);
        $this->entityManager->flush();

        // Automatycznie uruchom generowanie przypisań
        try {
            $generationResult = $this->scheduleGenerationService->generateSchedule($schedule->getId());
        } catch (\Exception $e) {
            // Log error but don't fail the schedule creation
            // You might want to add proper logging here
        }

        return $this->json($schedule, Response::HTTP_CREATED, [], [
            'groups' => ['schedule:read']
        ]);
    }

    #[Route('/{id}', name: 'api_schedule_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json(['error' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($schedule, Response::HTTP_OK, [], [
            'groups' => ['schedule:read']
        ]);
    }

    #[Route('/{id}', name: 'api_schedule_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json(['error' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['status'])) {
            $schedule->setStatus(ScheduleStatus::from($data['status']));
        }

        if (isset($data['weekStartDate'])) {
            try {
                $weekStartDate = new \DateTime($data['weekStartDate']);
                $schedule->setWeekStartDate($weekStartDate);
            } catch (\Exception $e) {
                return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
            }
        }

        $errors = $this->validator->validate($schedule);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($schedule, Response::HTTP_OK, [], [
            'groups' => ['schedule:read']
        ]);
    }

    #[Route('/{id}', name: 'api_schedule_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $schedule = $this->scheduleRepository->find($id);
        
        if (!$schedule) {
            return $this->json(['error' => 'Schedule not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($schedule);
        $this->entityManager->flush();

        return $this->json(['message' => 'Schedule deleted successfully'], Response::HTTP_OK);
    }


}