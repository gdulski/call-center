<?php

namespace App\Repository;

use App\Entity\User;
use App\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function save(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(User $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Znajdź agentów dla konkretnego typu kolejki
     */
    public function findAgentsByQueueType(int $queueTypeId): array
    {
        return $this->createQueryBuilder('u')
            ->join('u.queueTypes', 'aqt')
            ->andWhere('aqt.queueType = :queueTypeId')
            ->andWhere('u.role = :role')
            ->setParameter('queueTypeId', $queueTypeId)
            ->setParameter('role', UserRole::AGENT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all users with relations loaded
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('u')
            ->leftJoin('u.agentQueueTypes', 'aqt')
            ->leftJoin('aqt.queueType', 'qt')
            ->leftJoin('u.agentAvailabilities', 'aa')
            ->addSelect('aqt')
            ->addSelect('qt')
            ->addSelect('aa')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find agents by IDs with specific role
     */
    public function findAgentsByIds(array $agentIds): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.id IN (:agentIds)')
            ->andWhere('u.role = :role')
            ->setParameter('agentIds', $agentIds)
            ->setParameter('role', UserRole::AGENT)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find agents with efficiency scores for a specific queue type
     */
    public function findAgentsWithEfficiencyByQueueType(int $queueTypeId): array
    {
        $results = $this->createQueryBuilder('u')
            ->select('u', 'aqt.efficiencyScore')
            ->join('App\Entity\AgentQueueType', 'aqt', 'WITH', 'aqt.user = u')
            ->where('aqt.queueType = :queueTypeId')
            ->andWhere('u.role = :role')
            ->setParameter('queueTypeId', $queueTypeId)
            ->setParameter('role', UserRole::AGENT)
            ->orderBy('aqt.efficiencyScore', 'DESC')
            ->getQuery()
            ->getResult();
        
        $agents = [];
        foreach ($results as $result) {
            $agents[] = [
                'user' => $result[0],
                'efficiencyScore' => $result['efficiencyScore']
            ];
        }
        
        return $agents;
    }
} 