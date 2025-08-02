<?php

namespace App\Repository;

use App\Entity\AgentQueueType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AgentQueueType>
 *
 * @method AgentQueueType|null find($id, $lockMode = null, $lockVersion = null)
 * @method AgentQueueType|null findOneBy(array $criteria, array $orderBy = null)
 * @method AgentQueueType[]    findAll()
 * @method AgentQueueType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AgentQueueTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AgentQueueType::class);
    }

    public function save(AgentQueueType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AgentQueueType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 