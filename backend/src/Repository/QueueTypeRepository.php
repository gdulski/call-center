<?php

namespace App\Repository;

use App\Entity\QueueType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<QueueType>
 *
 * @method QueueType|null find($id, $lockMode = null, $lockVersion = null)
 * @method QueueType|null findOneBy(array $criteria, array $orderBy = null)
 * @method QueueType[]    findAll()
 * @method QueueType[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class QueueTypeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, QueueType::class);
    }

    public function save(QueueType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(QueueType $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
} 