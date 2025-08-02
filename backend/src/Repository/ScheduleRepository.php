<?php

namespace App\Repository;

use App\Entity\Schedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Schedule>
 *
 * @method Schedule|null find($id, $lockMode = null, $lockVersion = null)
 * @method Schedule|null findOneBy(array $criteria, array $orderBy = null)
 * @method Schedule[]    findAll()
 * @method Schedule[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Schedule::class);
    }

    public function save(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Schedule $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find schedule by queue type and week start date
     */
    public function findByQueueTypeAndWeek(int $queueTypeId, \DateTimeInterface $weekStartDate): ?Schedule
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.queueType = :queueType')
            ->andWhere('s.weekStartDate = :weekStartDate')
            ->setParameter('queueType', $queueTypeId)
            ->setParameter('weekStartDate', $weekStartDate)
            ->getQuery()
            ->getOneOrNullResult();
    }
}