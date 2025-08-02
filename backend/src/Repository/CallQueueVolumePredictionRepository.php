<?php

namespace App\Repository;

use App\Entity\CallQueueVolumePrediction;
use App\Entity\QueueType;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CallQueueVolumePrediction>
 *
 * @method CallQueueVolumePrediction|null find($id, $lockMode = null, $lockVersion = null)
 * @method CallQueueVolumePrediction|null findOneBy(array $criteria, array $orderBy = null)
 * @method CallQueueVolumePrediction[]    findAll()
 * @method CallQueueVolumePrediction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CallQueueVolumePredictionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CallQueueVolumePrediction::class);
    }

    public function save(CallQueueVolumePrediction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(CallQueueVolumePrediction $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Znajdź predykcje dla konkretnego QueueType w określonym dniu
     */
    public function findByQueueTypeAndDate(QueueType $queueType, \DateTimeInterface $date): array
    {
        $startOfDay = (clone $date)->setTime(0, 0, 0);
        $endOfDay = (clone $date)->setTime(23, 59, 59);

        return $this->createQueryBuilder('c')
            ->andWhere('c.queueType = :queueType')
            ->andWhere('c.hour >= :startOfDay')
            ->andWhere('c.hour <= :endOfDay')
            ->setParameter('queueType', $queueType)
            ->setParameter('startOfDay', $startOfDay)
            ->setParameter('endOfDay', $endOfDay)
            ->orderBy('c.hour', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Znajdź predykcje dla konkretnej godziny wszystkich QueueType
     */
    public function findByHour(\DateTimeInterface $hour): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.hour = :hour')
            ->setParameter('hour', $hour)
            ->getQuery()
            ->getResult();
    }
}