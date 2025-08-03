<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Entity\AgentQueueType;
use App\Enum\UserRole;
use App\Repository\UserRepository;
use App\Repository\QueueTypeRepository;
use App\Repository\AgentQueueTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/users')]
class UserController extends AbstractController
{
    public function __construct(
        private UserRepository $userRepository,
        private QueueTypeRepository $queueTypeRepository,
        private AgentQueueTypeRepository $agentQueueTypeRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_users_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $users = $this->userRepository->findAll();
        
        return $this->json($users, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('', name: 'api_users_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['role']) || empty(trim($data['role']))) {
            return $this->json(['error' => 'Role is required'], Response::HTTP_BAD_REQUEST);
        }

        // Validate role
        $roleValue = trim($data['role']);
        $validRoles = array_column(UserRole::cases(), 'value');
        if (!in_array($roleValue, $validRoles)) {
            return $this->json(['error' => 'Invalid role. Valid roles: ' . implode(', ', $validRoles)], Response::HTTP_BAD_REQUEST);
        }

        // Check if user with this name already exists
        $existingUser = $this->userRepository->findOneBy(['name' => trim($data['name'])]);
        if ($existingUser) {
            return $this->json(['error' => 'User with this name already exists'], Response::HTTP_CONFLICT);
        }

        $user = new User();
        $user->setName(trim($data['name']));
        $user->setRole(UserRole::from($roleValue));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Handle queue type assignments
        if (isset($data['queueTypeIds']) && is_array($data['queueTypeIds'])) {
            $this->updateUserQueueTypes($user, $data['queueTypeIds']);
        }

        return $this->json($user, Response::HTTP_CREATED, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/{id}', name: 'api_users_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        if (!isset($data['role']) || empty(trim($data['role']))) {
            return $this->json(['error' => 'Role is required'], Response::HTTP_BAD_REQUEST);
        }

        // Validate role
        $roleValue = trim($data['role']);
        $validRoles = array_column(UserRole::cases(), 'value');
        if (!in_array($roleValue, $validRoles)) {
            return $this->json(['error' => 'Invalid role. Valid roles: ' . implode(', ', $validRoles)], Response::HTTP_BAD_REQUEST);
        }

        // Check if another user with this name already exists
        $existingUser = $this->userRepository->findOneBy(['name' => trim($data['name'])]);
        if ($existingUser && $existingUser->getId() !== $user->getId()) {
            return $this->json(['error' => 'User with this name already exists'], Response::HTTP_CONFLICT);
        }

        $user->setName(trim($data['name']));
        $user->setRole(UserRole::from($roleValue));

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        // Handle queue type assignments
        if (isset($data['queueTypeIds']) && is_array($data['queueTypeIds'])) {
            $this->updateUserQueueTypes($user, $data['queueTypeIds']);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    #[Route('/roles', name: 'api_users_roles', methods: ['GET'])]
    public function getRoles(): JsonResponse
    {
        $roles = [];
        foreach (UserRole::cases() as $role) {
            $roles[] = [
                'value' => $role->value,
                'label' => $role->value
            ];
        }

        return $this->json($roles, Response::HTTP_OK);
    }

    #[Route('/queue-types', name: 'api_users_queue_types', methods: ['GET'])]
    public function getQueueTypes(): JsonResponse
    {
        $queueTypes = $this->queueTypeRepository->findAll();
        
        return $this->json($queueTypes, Response::HTTP_OK, [], [
            'groups' => ['queue_type:read']
        ]);
    }

    #[Route('/{id}', name: 'api_users_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $user = $this->userRepository->find($id);
        
        if (!$user) {
            return $this->json(['error' => 'User not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($user, Response::HTTP_OK, [], [
            'groups' => ['user:read']
        ]);
    }

    private function updateUserQueueTypes(User $user, array $queueTypeData): void
    {
        // Remove existing assignments
        foreach ($user->getAgentQueueTypes() as $agentQueueType) {
            $this->entityManager->remove($agentQueueType);
        }

        // Add new assignments
        foreach ($queueTypeData as $data) {
            $queueTypeId = is_array($data) ? $data['id'] : $data;
            $efficiencyScore = is_array($data) && isset($data['efficiencyScore']) ? (float)$data['efficiencyScore'] : 0.00;
            
            $queueType = $this->queueTypeRepository->find((int)$queueTypeId);
            if ($queueType) {
                $agentQueueType = new AgentQueueType();
                $agentQueueType->setUser($user);
                $agentQueueType->setQueueType($queueType);
                $agentQueueType->setEfficiencyScore($efficiencyScore);
                $this->entityManager->persist($agentQueueType);
            }
        }

        $this->entityManager->flush();
    }
}