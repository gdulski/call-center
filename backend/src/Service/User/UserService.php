<?php

declare(strict_types=1);

namespace App\Service\User;

use App\Entity\User;
use App\Entity\AgentQueueType;
use App\Entity\QueueType;
use App\Enum\UserRole;
use App\Exception\UserValidationException;
use App\Repository\UserRepository;
use App\Repository\QueueTypeRepository;
use App\DTO\User\UserRoleResponse;
use Doctrine\ORM\EntityManagerInterface;

final readonly class UserService
{
    public function __construct(
        private UserRepository $userRepository,
        private QueueTypeRepository $queueTypeRepository,
        private EntityManagerInterface $entityManager
    ) {}

    public function createUser(string $name, string $role, ?array $queueTypeIds = null): User
    {
        try {
            $user = new User();
            $user->setName(trim($name));
            $user->setRole(UserRole::from($role));

            $this->entityManager->persist($user);
            $this->entityManager->flush();

            if ($queueTypeIds !== null) {
                $this->updateUserQueueTypes($user, $queueTypeIds);
                $this->entityManager->flush();
            }

            return $user;
        } catch (\Exception $e) {
            throw new UserValidationException(['Failed to create user: ' . $e->getMessage()]);
        }
    }

    public function updateUser(User $user, string $name, string $role, ?array $queueTypeIds = null): void
    {
        try {
            $user->setName(trim($name));
            $user->setRole(UserRole::from($role));

            if ($queueTypeIds !== null) {
                $this->updateUserQueueTypes($user, $queueTypeIds);
            }

            $this->entityManager->flush();
        } catch (\Exception $e) {
            throw new UserValidationException(['Failed to update user: ' . $e->getMessage()]);
        }
    }

    public function findUserById(int $id): ?User
    {
        return $this->userRepository->find($id);
    }

    public function isNameUnique(string $name, ?User $excludeUser = null): bool
    {
        $existingUser = $this->userRepository->findOneBy(['name' => $name]);

        if (!$existingUser) {
            return true;
        }

        if ($excludeUser && $existingUser->getId() === $excludeUser->getId()) {
            return true;
        }

        return false;
    }



    /**
     * @return UserRoleResponse[]
     */
    public function getAvailableRoles(): array
    {
        $roles = [];
        foreach (UserRole::cases() as $role) {
            $roles[] = new UserRoleResponse(
                value: $role->value,
                label: $role->value
            );
        }

        return $roles;
    }

    /**
     * @return string[]
     */
    public function getAvailableRoleValues(): array
    {
        return array_column(UserRole::cases(), 'value');
    }

    private function updateUserQueueTypes(User $user, array $queueTypeData): void
    {
        $this->removeExistingQueueTypeAssignments($user);
        $this->addNewQueueTypeAssignments($user, $queueTypeData);
    }

    private function removeExistingQueueTypeAssignments(User $user): void
    {
        foreach ($user->getAgentQueueTypes() as $agentQueueType) {
            $this->entityManager->remove($agentQueueType);
        }
    }

    private function addNewQueueTypeAssignments(User $user, array $queueTypeData): void
    {
        foreach ($queueTypeData as $data) {
            $queueTypeId = $this->extractQueueTypeId($data);
            $efficiencyScore = $this->extractEfficiencyScore($data);
            
            $queueType = $this->queueTypeRepository->find((int)$queueTypeId);
            if ($queueType) {
                $this->createAgentQueueType($user, $queueType, $efficiencyScore);
            }
        }
    }

    private function extractQueueTypeId(mixed $data): mixed
    {
        return is_array($data) ? $data['id'] : $data;
    }

    private function extractEfficiencyScore(mixed $data): float
    {
        return is_array($data) && isset($data['efficiencyScore']) 
            ? (float)$data['efficiencyScore'] 
            : 0.00;
    }

    private function createAgentQueueType(User $user, QueueType $queueType, float $efficiencyScore): void
    {
        $agentQueueType = new AgentQueueType();
        $agentQueueType->setUser($user);
        $agentQueueType->setQueueType($queueType);
        $agentQueueType->setEfficiencyScore($efficiencyScore);
        
        $this->entityManager->persist($agentQueueType);
    }
}
