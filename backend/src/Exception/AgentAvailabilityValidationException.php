<?php

declare(strict_types=1);

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AgentAvailabilityValidationException extends BadRequestHttpException
{
    /**
     * @param string[] $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Agent availability validation failed',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $previous, $code);
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
