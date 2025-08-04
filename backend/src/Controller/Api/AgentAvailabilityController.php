<?php

namespace App\Controller\Api;

use App\Entity\AgentAvailability;
use App\Entity\User;
use App\Repository\AgentAvailabilityRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/availability')]
class AgentAvailabilityController extends AbstractController
{
    public function __construct(
        private AgentAvailabilityRepository $availabilityRepository,
        private UserRepository $userRepository,
        private EntityManagerInterface $entityManager,
        private SerializerInterface $serializer,
        private ValidatorInterface $validator
    ) {}

    #[Route('', name: 'api_availability_index', methods: ['GET'])]
    public function index(Request $request): JsonResponse
    {
        $agentId = $request->query->get('agentId');
        
        if ($agentId) {
            $agent = $this->userRepository->find($agentId);
            if (!$agent) {
                return $this->json(['error' => 'Agent not found'], Response::HTTP_NOT_FOUND);
            }
            $availabilities = $this->availabilityRepository->findBy(['agent' => $agent]);
        } else {
            $availabilities = $this->availabilityRepository->findAll();
        }
        
        return $this->json($availabilities, Response::HTTP_OK, [], [
            'groups' => ['availability:read']
        ]);
    }

    #[Route('', name: 'api_availability_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['agentId']) || !isset($data['startDate']) || !isset($data['endDate'])) {
            return $this->json(['error' => 'Agent ID, start date and end date are required'], Response::HTTP_BAD_REQUEST);
        }

        $agent = $this->userRepository->find($data['agentId']);
        if (!$agent) {
            return $this->json(['error' => 'Agent not found'], Response::HTTP_NOT_FOUND);
        }

        try {
            $startDate = new \DateTime($data['startDate']);
            $endDate = new \DateTime($data['endDate']);
            
            // Zaokrąglij do pełnych godzin
            $startDate = $this->roundToFullHour($startDate);
            $endDate = $this->roundToFullHour($endDate);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        if ($startDate >= $endDate) {
            return $this->json(['error' => 'Start date must be before end date'], Response::HTTP_BAD_REQUEST);
        }

        // Check for overlapping availability periods
        $overlapping = $this->availabilityRepository->createQueryBuilder('a')
            ->where('a.agent = :agent')
            ->andWhere('(a.startDate <= :endDate AND a.endDate >= :startDate)')
            ->setParameter('agent', $agent)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        if (!empty($overlapping)) {
            return $this->json(['error' => 'Availability period overlaps with existing period'], Response::HTTP_CONFLICT);
        }

        $availability = new AgentAvailability();
        $availability->setAgent($agent);
        $availability->setStartDate($startDate);
        $availability->setEndDate($endDate);

        $errors = $this->validator->validate($availability);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->persist($availability);
        $this->entityManager->flush();

        return $this->json($availability, Response::HTTP_CREATED, [], [
            'groups' => ['availability:read']
        ]);
    }

    #[Route('/{id}', name: 'api_availability_show', methods: ['GET'])]
    public function show(int $id): JsonResponse
    {
        $availability = $this->availabilityRepository->find($id);
        
        if (!$availability) {
            return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json($availability, Response::HTTP_OK, [], [
            'groups' => ['availability:read']
        ]);
    }

    #[Route('/{id}', name: 'api_availability_update', methods: ['PUT'])]
    public function update(int $id, Request $request): JsonResponse
    {
        $availability = $this->availabilityRepository->find($id);
        
        if (!$availability) {
            return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
        }

        $data = json_decode($request->getContent(), true);

        if (!isset($data['startDate']) || !isset($data['endDate'])) {
            return $this->json(['error' => 'Start date and end date are required'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $startDate = new \DateTime($data['startDate']);
            $endDate = new \DateTime($data['endDate']);
            
            // Zaokrąglij do pełnych godzin
            $startDate = $this->roundToFullHour($startDate);
            $endDate = $this->roundToFullHour($endDate);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Invalid date format'], Response::HTTP_BAD_REQUEST);
        }

        if ($startDate >= $endDate) {
            return $this->json(['error' => 'Start date must be before end date'], Response::HTTP_BAD_REQUEST);
        }

        // Check for overlapping availability periods (excluding current one)
        $overlapping = $this->availabilityRepository->createQueryBuilder('a')
            ->where('a.agent = :agent')
            ->andWhere('a.id != :currentId')
            ->andWhere('(a.startDate <= :endDate AND a.endDate >= :startDate)')
            ->setParameter('agent', $availability->getAgent())
            ->setParameter('currentId', $availability->getId())
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->getQuery()
            ->getResult();

        if (!empty($overlapping)) {
            return $this->json(['error' => 'Availability period overlaps with existing period'], Response::HTTP_CONFLICT);
        }

        $availability->setStartDate($startDate);
        $availability->setEndDate($endDate);

        $errors = $this->validator->validate($availability);
        if (count($errors) > 0) {
            $errorMessages = [];
            foreach ($errors as $error) {
                $errorMessages[] = $error->getMessage();
            }
            return $this->json(['errors' => $errorMessages], Response::HTTP_BAD_REQUEST);
        }

        $this->entityManager->flush();

        return $this->json($availability, Response::HTTP_OK, [], [
            'groups' => ['availability:read']
        ]);
    }

    #[Route('/{id}', name: 'api_availability_delete', methods: ['DELETE'])]
    public function delete(int $id): JsonResponse
    {
        $availability = $this->availabilityRepository->find($id);
        
        if (!$availability) {
            return $this->json(['error' => 'Availability not found'], Response::HTTP_NOT_FOUND);
        }

        $this->entityManager->remove($availability);
        $this->entityManager->flush();

        return $this->json(['message' => 'Availability deleted successfully'], Response::HTTP_OK);
    }

    /**
     * Zaokrągla datę do pełnej godziny (ustaw minuty, sekundy i mikrosekundy na 0)
     */
    private function roundToFullHour(\DateTimeInterface $dateTime): \DateTime
    {
        $rounded = new \DateTime($dateTime->format('Y-m-d H:i:s'), $dateTime->getTimezone());
        $rounded->setTime(
            (int)$rounded->format('H'), // godzina
            0, // minuty = 0
            0, // sekundy = 0
            0  // mikrosekundy = 0
        );
        return $rounded;
    }
}