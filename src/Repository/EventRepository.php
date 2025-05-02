<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\Category;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }
    
    /**
     * Find events by custom filters
     *
     * @param array $filters The filters to apply
     *        Possible filters:
     *        - category: string|array - Category name(s)
     *        - organizer: User - The event organizer
     *        - search: string - Search in title and description
     *        - date_from: \DateTime - Events after this date
     *        - date_to: \DateTime - Events before this date
     *        - approved: bool - Filter by approval status
     *        - upcoming: bool - Only upcoming events
     *        - location: string - Filter by location (partial match)
     * @param array $orderBy Field and direction to order by (e.g. ['date' => 'ASC'])
     * @param int|null $limit Maximum number of results
     * @param int|null $offset Offset for pagination
     * 
     * @return Event[] Returns an array of Event objects
     */
    public function findByFilters(
        array $filters = [],
        array $orderBy = ['date' => 'ASC'],
        ?int $limit = null,
        ?int $offset = null
    ): array {
        $qb = $this->createQueryBuilder('e');
        
        // Apply filters
        if (!empty($filters)) {
            $this->applyFilters($qb, $filters);
        }
        
        // Apply ordering
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.$field", $direction);
        }
        
        // Apply limit and offset
        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }
        
        if ($offset !== null) {
            $qb->setFirstResult($offset);
        }
        
        return $qb->getQuery()->getResult();
    }
    
    /**
     * Apply filters to the query builder
     *
     * @param QueryBuilder $qb Query builder to modify
     * @param array $filters Filters to apply
     */
    private function applyFilters(QueryBuilder $qb, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if ($value === null) {
                continue;
            }
            
            switch ($key) {
                case 'category':
                    if (is_array($value)) {
                        $qb->innerJoin('e.categories', 'c')
                           ->andWhere('c IN (:categories)')
                           ->setParameter('categories', $value);
                    } elseif ($value instanceof Category) {
                        $qb->innerJoin('e.categories', 'c')
                           ->andWhere('c = :category')
                           ->setParameter('category', $value);
                    } elseif (is_numeric($value)) {
                        $qb->innerJoin('e.categories', 'c')
                           ->andWhere('c.id = :categoryId')
                           ->setParameter('categoryId', $value);
                    }
                    break;
                    
                case 'organizer':
                    $qb->andWhere('e.organizer = :organizer')
                       ->setParameter('organizer', $value);
                    break;
                    
                case 'search':
                    $qb->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->like('e.title', ':search'),
                            $qb->expr()->like('e.description', ':search')
                        )
                    )
                    ->setParameter('search', '%' . $value . '%');
                    break;
                    
                case 'date_from':
                    $qb->andWhere('e.date >= :dateFrom')
                       ->setParameter('dateFrom', $value);
                    break;
                    
                case 'date_to':
                    $qb->andWhere('e.date <= :dateTo')
                       ->setParameter('dateTo', $value);
                    break;
                    
                case 'approved':
                    $qb->andWhere('e.is_approved = :approved')
                       ->setParameter('approved', (bool) $value);
                    break;
                    
                case 'upcoming':
                    if ($value) {
                        $qb->andWhere('e.date >= :today')
                           ->setParameter('today', new \DateTime('today'));
                    }
                    break;
                    
                case 'location':
                    $qb->andWhere('e.location LIKE :location')
                       ->setParameter('location', '%' . $value . '%');
                    break;
            }
        }
    }
    
    /**
     * Find upcoming events
     *
     * @param int $limit Maximum number of results
     * @return Event[] Returns an array of upcoming Event objects
     */
    public function findUpcomingEvents(int $limit = 5): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date >= :today')
            ->andWhere('e.is_approved = :approved')
            ->setParameter('today', new \DateTime('today'))
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->addOrderBy('e.time', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Find events organized by a specific user
     *
     * @param User $user The organizing user
     * @return Event[] Returns an array of Event objects
     */
    public function findByOrganizer(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.organizer = :user')
            ->setParameter('user', $user)
            ->orderBy('e.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events by category
     *
     * @param Category $category The category to filter by
     * @return Event[] Returns an array of Event objects
     */
    public function findByCategory(Category $category): array
    {
        return $this->createQueryBuilder('e')
            ->innerJoin('e.categories', 'c')
            ->andWhere('c = :category')
            ->andWhere('e.is_approved = :approved')
            ->setParameter('category', $category)
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all approved events
     *
     * @return Event[] Returns an array of approved Event objects
     */
    public function findApprovedEvents(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.is_approved = :approved')
            ->setParameter('approved', true)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Create a query for filtered events (for use with KnpPaginator)
     *
     * @param array $filters The filters to apply
     * @param array $orderBy Field and direction to order by (e.g. ['date' => 'ASC'])
     * @return \Doctrine\ORM\Query The query object for pagination
     */
    public function createFilteredQuery(
        array $filters = [],
        array $orderBy = ['date' => 'ASC']
    ): \Doctrine\ORM\Query {
        $qb = $this->createQueryBuilder('e');
        
        // Apply filters
        if (!empty($filters)) {
            $this->applyFilters($qb, $filters);
        }
        
        // Apply ordering
        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy("e.$field", $direction);
        }
        
        return $qb->getQuery();
    }

    /**
     * Find expired events (events with a date that has passed)
     *
     * @param \DateTime $date The date to compare against
     * @return Event[] Returns an array of expired Event objects
     */
    public function findExpiredEvents(\DateTime $date): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.date < :date')
            ->setParameter('date', $date)
            ->orderBy('e.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}