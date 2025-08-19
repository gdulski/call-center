<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\CreateUserRequest;
use App\DTO\UpdateUserRequest;
use App\Entity\User;
use App\Exception\UserValidationException;
use App\Enum\UserRole;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UserValidationService
{
    public function __construct(
        private UserService $userService,
        private ValidatorInterface $validator
    ) {}

    public function validateCreateRequest(array $data): CreateUserRequest
    {
        $dto = CreateUserRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new UserValidationException($errorMessages);
        }

        if (!$this->userService->isNameUnique($dto->name)) {
            throw new UserValidationException(['User with this name already exists']);
        }

        if (!$this->isValidRole($dto->role)) {
            $validRoles = $this->userService->getAvailableRoleValues();
            throw new UserValidationException(['Invalid role. Valid roles: ' . implode(', ', $validRoles)]);
        }

        return $dto;
    }

    public function validateUpdateRequest(array $data, User $user): UpdateUserRequest
    {
        $dto = UpdateUserRequest::fromArray($data);
        
        $errors = $this->validator->validate($dto);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            throw new UserValidationException($errorMessages);
        }

        if (!$this->userService->isNameUnique($dto->name, $user)) {
            throw new UserValidationException(['User with this name already exists']);
        }

        if (!$this->isValidRole($dto->role)) {
            $validRoles = $this->userService->getAvailableRoleValues();
            throw new UserValidationException(['Invalid role. Valid roles: ' . implode(', ', $validRoles)]);
        }

        return $dto;
    }

    private function isValidRole(string $role): bool
    {
        $validRoles = $this->userService->getAvailableRoleValues();
        return in_array($role, $validRoles);
    }
}
