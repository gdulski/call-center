<?php

declare(strict_types=1);

namespace App\DTO\User;

final readonly class UserRoleResponse
{
    public function __construct(
        public string $value,
        public string $label
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            value: $data['value'],
            label: $data['label']
        );
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value,
            'label' => $this->label
        ];
    }
}
