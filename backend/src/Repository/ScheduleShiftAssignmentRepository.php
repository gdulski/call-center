<?php

namespace App\Repository;

use App\Entity\ScheduleShiftAssignment;
use App\Entity\Schedule;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ScheduleShiftAssignment>
 *
 * @method ScheduleShiftAssignment|null find($id, $lockMode = null, $lockVersion = null)
 * @method ScheduleShiftAssignment|null findOneBy(array $criteria, array $orderBy = null)
 * @method ScheduleShiftAssignment[]    findAll()
 * @method ScheduleShiftAssignment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ScheduleShiftAssignmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ScheduleShiftAssignment::class);
    }

    public function save(ScheduleShiftAssignment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ScheduleShiftAssignment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find assignments for a specific schedule
     */
    public function findBySchedule(Schedule $schedule): array
    {
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.schedule = :schedule')
            ->setParameter('schedule', $schedule)
            ->orderBy('sa.date', 'ASC')
            ->addOrderBy('sa.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find assignments for a specific user in a date range
     */
    public function findByUserAndDateRange(User $user, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        return $this->createQueryBuilder('sa')
            ->andWhere('sa.user = :user')
            ->andWhere('sa.date >= :startDate')
            ->andWhere('sa.date <= :endDate')
            ->setParameter('user', $user)
            ->setParameter('startDate', $startDate)
            ->setParameter('endDate', $endDate)
            ->orderBy('sa.date', 'ASC')
            ->addOrderBy('sa.startTime', 'ASC')
            ->getQuery()
            ->getResult();
    }



    /**
     * Check if user has overlapping assignments
     */
    public function hasOverlappingAssignment(User $user, \DateTimeInterface $startTime, \DateTimeInterface $endTime, ?int $excludeId = null): bool
    {
        $qb = $this->createQueryBuilder('sa')
            ->select('COUNT(sa.id)')
            ->andWhere('sa.user = :user')
            ->andWhere('(
                (sa.startTime <= :startTime AND sa.endTime > :startTime) OR
                (sa.startTime < :endTime AND sa.endTime >= :endTime) OR
                (sa.startTime >= :startTime AND sa.endTime <= :endTime)
            )')
            ->setParameter('user', $user)
            ->setParameter('startTime', $startTime)
            ->setParameter('endTime', $endTime);

        if ($excludeId) {
            $qb->andWhere('sa.id != :excludeId')
               ->setParameter('excludeId', $excludeId);
        }

        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}