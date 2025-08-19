<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CreateAgentAvailabilityRequest;
use App\DTO\UpdateAgentAvailabilityRequest;
use App\Exception\AgentAvailabilityValidationException;
use App\Service\AgentAvailabilityService;
use App\Service\AgentAvailabilityValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/availability')]
final class AgentAvailabilityController extends BaseApiController
{
    public function __construct(
        private readonly AgentAvailabilityService $availabilityService,
        private readonly AgentAvailabilityValidationService $validationService
    ) {}

    #[Route('', name: 'api_availability_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        try {
            $agentId = $request->query->get('agentId');
            $agentId = $agentId ? (int)$agentId : null;
            
            $availabilities = $this->availabilityService->findAvailabilitiesByAgent($agentId);
            
                    return $this->successResponse($availabilities, Response::HTTP_OK, [
            'groups' => ['availability:read']
        ]);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('', name: 'api_availability_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            $createRequest = $this->validationService->validateCreateRequest($data);
            
            $availability = $this->availabilityService->createAvailability(
                $createRequest->agentId,
                $createRequest->startDate,
                $createRequest->endDate
            );

            return $this->successResponse($availability, Response::HTTP_CREATED, [
                'groups' => ['availability:read']
            ]);
        } catch (AgentAvailabilityValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_availability_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $availability = $this->availabilityService->findAvailabilityById($id);
            if (!$availability) {
                return $this->notFoundResponse('Availability not found');
            }

            return $this->successResponse($availability, Response::HTTP_OK, [
                'groups' => ['availability:read']
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_availability_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $availability = $this->availabilityService->findAvailabilityById($id);
            if (!$availability) {
                return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
            }

            $data = $this->getRequestData($request);
            $updateRequest = $this->validationService->validateUpdateRequest($data);
            
            $this->availabilityService->updateAvailability(
                $availability,
                $updateRequest->startDate,
                $updateRequest->endDate
            );

            return $this->successResponse($availability, Response::HTTP_OK, [
                'groups' => ['availability:read']
            ]);
        } catch (AgentAvailabilityValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_availability_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        try {
            $availability = $this->availabilityService->findAvailabilityById($id);
            if (!$availability) {
                return $this->notFoundResponse('Availability not found');
            }

            $this->availabilityService->deleteAvailability($availability);

            return $this->successResponse(['message' => 'Availability deleted successfully']);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }


}