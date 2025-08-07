<?php

namespace App\Repository;

use App\Entity\AgentAvailability;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Log\LoggerInterface;

/**
 * @extends ServiceEntityRepository<AgentAvailability>
 *
 * @method AgentAvailability|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentAvailability|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentAvailability[]    findAll()
 * @method AgentAvailability[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentAvailabilityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private LoggerInterface $logger)
    {
        parent::__construct($registry, AgentAvailability::class);
    }

    public function save(AgentAvailability $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentAvailability $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Znajdź dostępności agentów w określonym zakresie dat
     */
    public function findByAgentsAndDateRange(array $agentIds, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $this->logger->info('API Debug', ['findByAgentsAndDateRange']);

        $query =  $this->createQueryBuilder('a')
            ->andWhere('a.agent IN (:agentIds)')
            ->andWhere('a.startDate >= :startDate')
            ->andWhere('a.endDate <= :endDate')
            ->setParameter('agentIds', $agentIds)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('a.agent', 'ASC')
            ->addOrderBy('a.startDate', 'ASC')
            ->getQuery();
            // ->getResult();

        // $this->logger->info('API Debug', ['query !!' => $query->getSQL()]);

        return $query->getResult();
    }
} 