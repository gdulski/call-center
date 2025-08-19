<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\DTO\CreateUserRequest;
use App\DTO\UpdateUserRequest;
use App\Exception\UserValidationException;
use App\Repository\UserRepository;
use App\Repository\QueueTypeRepository;
use App\Service\UserService;
use App\Service\UserValidationService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/users')]
final class UserController extends BaseApiController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly QueueTypeRepository $queueTypeRepository,
        private readonly UserService $userService,
        private readonly UserValidationService $userValidationService
    ) {}

    #[Route('', name: 'api_users_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        return $this->successResponse($users, Response::HTTP_OK, [
            'groups' => ['user:read']
        ]);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        try {
            $data = $this->getRequestData($request);
            $createRequest = $this->userValidationService->validateCreateRequest($data);
            
            $user = $this->userService->createUser(
                $createRequest->name,
                $createRequest->role,
                $createRequest->queueTypeIds
            );

            return $this->successResponse($user, Response::HTTP_CREATED, [
                'groups' => ['user:read']
            ]);
        } catch (UserValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/roles', name: 'api_users_roles', methods: ['GET'])]
    public function getRoles(): JsonResponse
    {
        try {
            $roles = $this->userService->getAvailableRoles();

            return $this->successResponse($roles);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/queue-types', name: 'api_users_queue_types', methods: ['GET'])]
    public function getQueueTypes(): JsonResponse
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

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        try {
            $user = $this->userService->findUserById($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $data = $this->getRequestData($request);
            $updateRequest = $this->userValidationService->validateUpdateRequest($data, $user);
            
            $this->userService->updateUser(
                $user,
                $updateRequest->name,
                $updateRequest->role,
                $updateRequest->queueTypeIds
            );

            return $this->successResponse($user, Response::HTTP_OK, [
                'groups' => ['user:read']
            ]);
        } catch (UserValidationException $e) {
            return $this->validationErrorResponse($e->getErrors());
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        try {
            $user = $this->userService->findUserById($id);
            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            return $this->successResponse($user, Response::HTTP_OK, [
                'groups' => ['user:read']
            ]);
        } catch (\Exception $e) {
            return $this->internalServerErrorResponse();
        }
    }


}