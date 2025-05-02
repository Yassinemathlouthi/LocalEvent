<?php

namespace App\Service;

use App\Entity\Event;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class EventFilterService
{
    private EventRepository $eventRepository;
    private EntityManagerInterface $entityManager;
    
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->eventRepository = $entityManager->getRepository(Event::class);
        $this->entityManager = $entityManager;
    }
    
    /**
     * Get filtered events from request parameters
     *
     * @param Request $request The request containing filter parameters
     * @return \Doctrine\ORM\Query The query object for pagination
     */
    public function getFilteredEvents(Request $request): \Doctrine\ORM\Query
    {
        $filters = $this->extractFiltersFromRequest($request);
        
        // Determine order by parameters
        $orderBy = [];
        $sort = $request->query->get('sort', 'date');
        $direction = strtoupper($request->query->get('direction', 'ASC'));
        
        // Validate direction
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        
        // Validate sort field
        if (in_array($sort, ['date', 'title', 'created_at'])) {
            $orderBy[$sort] = $direction;
        } else {
            $orderBy['date'] = 'ASC';
        }
        
        return $this->eventRepository->createFilteredQuery($filters, $orderBy);
    }
    
    /**
     * Extract filters from the request query parameters
     *
     * @param Request $request The request containing filter parameters
     * @return array Extracted filters
     */
    private function extractFiltersFromRequest(Request $request): array
    {
        $filters = [];
        
        // Extract category filter
        if ($request->query->has('category') && !empty($request->query->get('category'))) {
            $categoryId = $request->query->getInt('category');
            if ($categoryId) {
                $category = $this->entityManager->getRepository('App\Entity\Category')->find($categoryId);
                if ($category) {
                    $filters['category'] = $category;
                }
            }
        }
        
        // Extract search
        if ($request->query->has('search') && !empty($request->query->get('search'))) {
            $filters['search'] = $request->query->get('search');
        }
        
        // Extract date filters
        if ($request->query->has('date_from') && !empty($request->query->get('date_from'))) {
            try {
                $filters['date_from'] = new \DateTime($request->query->get('date_from'));
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        
        if ($request->query->has('date_to') && !empty($request->query->get('date_to'))) {
            try {
                $filters['date_to'] = new \DateTime($request->query->get('date_to'));
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        
        // Extract location filter
        if ($request->query->has('location') && !empty($request->query->get('location'))) {
            $filters['location'] = $request->query->get('location');
        }
        
        // Extract approved status filter (only for admins, to be checked in controller)
        if ($request->query->has('approved')) {
            $filters['approved'] = filter_var($request->query->get('approved'), FILTER_VALIDATE_BOOLEAN);
        }
        
        // Extract upcoming filter
        if ($request->query->has('upcoming')) {
            $filters['upcoming'] = filter_var($request->query->get('upcoming'), FILTER_VALIDATE_BOOLEAN);
        }
        
        // Extract organizer filter (assuming organizer is passed as ID)
        if ($request->query->has('organizer') && !empty($request->query->get('organizer'))) {
            $organizerId = $request->query->getInt('organizer');
            if ($organizerId) {
                // Need to retrieve the user entity, not just pass the ID
                $user = $this->entityManager->getRepository('App\Entity\User')->find($organizerId);
                if ($user) {
                    $filters['organizer'] = $user;
                }
            }
        }
        
        return $filters;
    }
    
    /**
     * Get upcoming events
     *
     * @param int $limit Maximum number of results
     * @return array Upcoming events
     */
    public function getUpcomingEvents(int $limit = 5): array
    {
        return $this->eventRepository->findUpcomingEvents($limit);
    }
}
