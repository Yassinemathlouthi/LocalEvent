<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all approved events, ordered by date
     */
    public function findApprovedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isApproved = :approved')
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events waiting for approval
     */
    public function findPendingEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isApproved = :approved')
            ->setParameter('approved', false)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find upcoming events
     */
    public function findUpcomingEvents(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.isApproved = :approved')
            ->andWhere('e.date > :now')
            ->setParameter('approved', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('e.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by category
     */
    public function findByCategory(Category $category): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.category = :category')
            ->andWhere('e.isApproved = :approved')
            ->setParameter('category', $category)
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by location
     */
    public function findByLocation(string $location): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.location LIKE :location')
            ->andWhere('e.isApproved = :approved')
            ->setParameter('location', '%' . $location . '%')
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events for filtered search
     */
    public function findByFilters(?Category $category = null, ?string $location = null, ?\DateTime $date = null): QueryBuilder
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.isApproved = :approved')
            ->setParameter('approved', true);

        if ($category) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $category);
        }

        if ($location) {
            $qb->andWhere('e.location LIKE :location')
                ->setParameter('location', '%' . $location . '%');
        }

        if ($date) {
            $qb->andWhere('DATE(e.date) = :date')
                ->setParameter('date', $date->format('Y-m-d'));
        }

        $qb->orderBy('e.date', 'ASC');

        return $qb;
    }
}
