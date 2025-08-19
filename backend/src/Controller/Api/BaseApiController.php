<?php

declare(strict_types=1);

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class BaseApiController extends AbstractController
{
    protected function getRequestData(Request $request): array
    {
        return json_decode($request->getContent(), true) ?? [];
    }

    protected function successResponse($data, int $statusCode = Response::HTTP_OK, array $context = []): JsonResponse
    {
        return $this->json($data, $statusCode, [], $context);
    }

    protected function errorResponse(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return $this->json(['error' => $message], $statusCode);
    }

    protected function validationErrorResponse(array $errors): JsonResponse
    {
        return $this->json(['errors' => $errors], Response::HTTP_BAD_REQUEST);
    }

    protected function notFoundResponse(string $message = 'Resource not found'): JsonResponse
    {
        return $this->json(['error' => $message], Response::HTTP_NOT_FOUND);
    }

    protected function internalServerErrorResponse(): JsonResponse
    {
        return $this->json(['error' => 'Internal server error'], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}
