<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\Agent\AgentReassignmentRequest;
use App\DTO\Schedule\UpdateScheduleStatusRequest;
use App\Exception\ScheduleValidationException;
use App\Service\Schedule\ScheduleService;
use App\Service\Schedule\ScheduleValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/schedules', name: 'api_schedules_')]
final class ScheduleController extends BaseApiController
{
    public function __construct(
        private readonly ScheduleService $scheduleService,
        private readonly ScheduleValidationService $validationService,
        private readonly ValidatorInterface $validator
    ) {}

    #[Route('', name: 'list', methods: ['GET'])]
    public function list(): JsonResponse
    {
        try {
            $data = $this->scheduleService->findAllOrderedByWeekStartDate();
            
            return $this->successResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $schedule = $this->scheduleService->findScheduleById($id);
            
            if (!$schedule) {
                return $this->notFoundResponse('Harmonogram nie został znaleziony');
            }
            
            $data = $this->scheduleService->getScheduleDetails($schedule);
            
            return $this->successResponse([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('', name: 'create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            
            if (!$data) {
                return $this->errorResponse('Nieprawidłowe dane JSON');
            }
            
            $createRequest = $this->validationService->validateCreateRequest($data);
            
            $result = $this->scheduleService->createSchedule(
                $createRequest->queueTypeId,
                $createRequest->weekStartDate,
                $createRequest->optimizationType
            );
            
            return $this->successResponse([
                'success' => true,
                'message' => 'Harmonogram został utworzony i zoptymalizowany pomyślnie',
                'data' => $result
            ], 201);
            
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/generate', name: 'generate', methods: ['POST'])]
    public function generate(int $id): JsonResponse
    {
        try {
            $result = $this->scheduleService->generateSchedule($id);
            
            return $this->successResponse([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/optimize', name: 'optimize', methods: ['POST'])]
    public function optimize(int $id): JsonResponse
    {
        try {
            $result = $this->scheduleService->optimizeSchedule($id);
            
            return $this->successResponse([
                'success' => true,
                'message' => 'Harmonogram został zoptymalizowany pomyślnie',
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/optimize-ilp', name: 'optimize_ilp', methods: ['POST'])]
    public function optimizeILP(int $id): JsonResponse
    {
        try {
            $result = $this->scheduleService->optimizeILP($id);
            
            return $this->successResponse([
                'success' => true,
                'message' => 'Harmonogram został zoptymalizowany używając ILP',
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/metrics', name: 'metrics', methods: ['GET'])]
    public function metrics(int $id): JsonResponse
    {
        try {
            $result = $this->scheduleService->getScheduleMetrics($id);
            
            return $this->successResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/status', name: 'update_status', methods: ['PATCH'])]
    public function updateStatus(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            
            if (!$data) {
                return $this->errorResponse('Nieprawidłowe dane JSON');
            }
            
            $dto = UpdateScheduleStatusRequest::fromArray($data);
            $errors = $this->validator->validate($dto);
            
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->validationErrorResponse($errorMessages);
            }
            
            $result = $this->scheduleService->updateStatus($id, $dto->status);
            
            return $this->successResponse([
                'success' => true,
                'message' => 'Status harmonogramu został zaktualizowany',
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $this->scheduleService->deleteSchedule($id);
            
            return $this->successResponse([
                'success' => true,
                'message' => 'Harmonogram został usunięty'
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/reassignment-preview', name: 'reassignment_preview', methods: ['POST'])]
    public function reassignmentPreview(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            
            if (!$data) {
                return $this->errorResponse('Nieprawidłowe dane JSON');
            }
            
            $dto = AgentReassignmentRequest::fromArray($data);
            $errors = $this->validator->validate($dto);
            
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->validationErrorResponse($errorMessages);
            }
            
            $result = $this->scheduleService->getReassignmentPreview(
                $id,
                $dto->agentId,
                $dto->newAvailability
            );
            
            return $this->successResponse([
                'success' => true,
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}/reassign-agent', name: 'reassign_agent', methods: ['POST'])]
    public function reassignAgent(int $id, Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            
            if (!$data) {
                return $this->errorResponse('Nieprawidłowe dane JSON');
            }
            
            $dto = AgentReassignmentRequest::fromArray($data);
            $errors = $this->validator->validate($dto);
            
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }
                return $this->validationErrorResponse($errorMessages);
            }
            
            $result = $this->scheduleService->reassignAgent(
                $id,
                $dto->agentId,
                $dto->newAvailability
            );
            
            return $this->successResponse([
                'success' => true,
                'message' => $result['message'],
                'data' => $result
            ]);
        } catch (ScheduleValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }
}