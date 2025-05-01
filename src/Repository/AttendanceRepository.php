<?php

namespace App\Repository;

use App\Entity\Attendance;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Attendance>
 *
 * @method Attendance|null find($id, $lockMode = null, $lockVersion = null)
 * @method Attendance|null findOneBy(array $criteria, array $orderBy = null)
 * @method Attendance[]    findAll()
 * @method Attendance[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AttendanceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Attendance::class);
    }

    public function save(Attendance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Attendance $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find event attendances
     */
    public function findEventAttendances(Event $event): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.event = :event')
            ->andWhere('a.status = :status')
            ->setParameter('event', $event)
            ->setParameter('status', Attendance::STATUS_JOINED)
            ->orderBy('a.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find user attendances
     */
    public function findUserAttendances(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', Attendance::STATUS_JOINED)
            ->orderBy('a.joinedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if user is attending an event
     */
    public function isAttending(User $user, Event $event): bool
    {
        $result = $this->createQueryBuilder('a')
            ->where('a.user = :user')
            ->andWhere('a.event = :event')
            ->andWhere('a.status = :status')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->setParameter('status', Attendance::STATUS_JOINED)
            ->getQuery()
            ->getOneOrNullResult();
        
        return $result !== null;
    }

    /**
     * Get attendance statistics
     */
    public function getStatistics(): array
    {
        $totalAttendances = $this->createQueryBuilder('a')
            ->select('COUNT(a.id)')
            ->where('a.status = :status')
            ->setParameter('status', Attendance::STATUS_JOINED)
            ->getQuery()
            ->getSingleScalarResult();
            
        $mostPopularEvents = $this->createQueryBuilder('a')
            ->select('e.id, e.title, COUNT(a.id) as attendeeCount')
            ->join('a.event', 'e')
            ->where('a.status = :status')
            ->andWhere('e.isApproved = :approved')
            ->setParameter('status', Attendance::STATUS_JOINED)
            ->setParameter('approved', true)
            ->groupBy('e.id')
            ->orderBy('attendeeCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
            
        return [
            'totalAttendances' => $totalAttendances,
            'mostPopularEvents' => $mostPopularEvents
        ];
    }
}
