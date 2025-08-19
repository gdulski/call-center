<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CreateQueueTypeRequest;
use App\DTO\UpdateQueueTypeRequest;
use App\Exception\QueueTypeValidationException;
use App\Repository\QueueTypeRepository;
use App\Service\QueueTypeService;
use App\Service\QueueTypeValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/queue-types')]
final class QueueTypeController extends BaseApiController
{
    public function __construct(
        private readonly QueueTypeRepository $queueTypeRepository,
        private readonly QueueTypeService $queueTypeService,
        private readonly QueueTypeValidationService $queueTypeValidationService
    ) {}

    #[Route('', name: 'api_queue_types_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
                try {
            $queueTypes = $this->queueTypeRepository->findAll();
            
            return $this->successResponse($queueTypes, Response::HTTP_OK, [
                'groups' => ['queue_type:read']
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('', name: 'api_queue_types_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            $createRequest = $this->queueTypeValidationService->validateCreateRequest($data);
            
            $queueType = $this->queueTypeService->createQueueType($createRequest->name);

            return $this->successResponse($queueType, Response::HTTP_CREATED, [
                'groups' => ['queue_type:read']
            ]);
        } catch (QueueTypeValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_queue_types_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $queueType = $this->queueTypeService->findQueueTypeById($id);
            if (!$queueType) {
                return $this->json(['error' => 'Queue type not found'], Response::HTTP_NOT_FOUND);
            }

            $data = $this->getRequestData($request);
            $updateRequest = $this->queueTypeValidationService->validateUpdateRequest($data, $queueType);
            
            $this->queueTypeService->updateQueueType($queueType, $updateRequest->name);

            return $this->successResponse($queueType, Response::HTTP_OK, [
                'groups' => ['queue_type:read']
            ]);
        } catch (QueueTypeValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_queue_types_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $queueType = $this->queueTypeService->findQueueTypeById($id);
            if (!$queueType) {
                return $this->notFoundResponse('Queue type not found');
            }

            return $this->successResponse($queueType, Response::HTTP_OK, [
                'groups' => ['queue_type:read']
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }


}