<?php

namespace App\Controller\Api;

use App\Entity\QueueType;
use App\Repository\QueueTypeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/queue-types')]
class QueueTypeController extends AbstractController
{
    public function __construct(
        private QueueTypeRepository $queueTypeRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_queue_types_index', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $queueTypes = $this->queueTypeRepository->findAll();
        
        return $this->json($queueTypes, Response::HTTP_OK, [], [
            'groups' => ['queue_type:read']
        ]);
    }

    #[Route('', name: 'api_queue_types_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if queue type with this name already exists
        $existingQueueType = $this->queueTypeRepository->findOneBy(['name' => trim($data['name'])]);
        if ($existingQueueType) {
            return $this->json(['error' => 'Queue type with this name already exists'], Response::HTTP_CONFLICT);
        }

        $queueType = new QueueType();
        $queueType->setName(trim($data['name']));

        $errors = $this->validator->validate($queueType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($queueType);
        $this->entityManager->flush();

        return $this->json($queueType, Response::HTTP_CREATED, [], [
            'groups' => ['queue_type:read']
        ]);
    }

    #[Route('/{id}', name: 'api_queue_types_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $queueType = $this->queueTypeRepository->find($id);
        
        if (!$queueType) {
            return $this->json(['error' => 'Queue type not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['name']) || empty(trim($data['name']))) {
            return $this->json(['error' => 'Name is required'], Response::HTTP_BAD_REQUEST);
        }

        // Check if another queue type with this name already exists
        $existingQueueType = $this->queueTypeRepository->findOneBy(['name' => trim($data['name'])]);
        if ($existingQueueType && $existingQueueType->getId() !== $queueType->getId()) {
            return $this->json(['error' => 'Queue type with this name already exists'], Response::HTTP_CONFLICT);
        }

        $queueType->setName(trim($data['name']));

        $errors = $this->validator->validate($queueType);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($queueType, Response::HTTP_OK, [], [
            'groups' => ['queue_type:read']
        ]);
    }

    #[Route('/{id}', name: 'api_queue_types_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $queueType = $this->queueTypeRepository->find($id);
        
        if (!$queueType) {
            return $this->json(['error' => 'Queue type not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($queueType, Response::HTTP_OK, [], [
            'groups' => ['queue_type:read']
        ]);
    }
}