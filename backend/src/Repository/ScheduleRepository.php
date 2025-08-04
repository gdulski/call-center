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
     * Find schedule by queue type and week identifier (YYYY-WW format)
     */
    public function findByQueueTypeAndWeekIdentifier(int $queueTypeId, string $weekIdentifier): ?Schedule
    {
        // Parse week identifier (YYYY-WW format)
        $parts = explode('-', $weekIdentifier);
        if (count($parts) !== 2) {
            return null;
        }
        
        $year = (int)$parts[0];
        $week = (int)$parts[1];
        
        // Calculate the start date of the week
        $startDate = $this->getDateOfISOWeek($year, $week);
        
        return $this->createQueryBuilder('s')
            ->andWhere('s.queueType = :queueType')
            ->andWhere('s.weekStartDate = :startDate')
            ->setParameter('queueType', $queueTypeId)
            ->setParameter('startDate', $startDate)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Get the start date of ISO week
     */
    private function getDateOfISOWeek(int $year, int $week): \DateTime
    {
        $simple = new \DateTime($year . '-01-01');
        $simple->modify('+' . (($week - 1) * 7) . ' days');
        $dayOfWeek = (int)$simple->format('N'); // 1 = Monday, 7 = Sunday
        
        if ($dayOfWeek > 1) {
            $simple->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        return $simple;
    }

    /**
     * Find schedule by queue type and week start date (for backward compatibility)
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

    /**
     * Find all schedules ordered by week start date ascending
     */
    public function findAllOrderedByWeekStartDate(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.weekStartDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}