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
        
        // If no filters are applied, show only upcoming approved events
        if (empty($filters)) {
            $filters['upcoming'] = true;
            $filters['approved'] = true;
        }
        
        // Always show only approved events for normal users
        if (!isset($filters['approved'])) {
            $filters['approved'] = true;
        }
        
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
        
        // Extract search query
        if ($request->query->has('search') && !empty($request->query->get('search'))) {
            $filters['search'] = $request->query->get('search');
        }
        
        // Extract date filter - this is a single date field in the form, used as a date_from filter
        if ($request->query->has('date') && !empty($request->query->get('date'))) {
            try {
                $date = new \DateTime($request->query->get('date'));
                $filters['date_from'] = $date;
            } catch (\Exception $e) {
                // Invalid date format, ignore
            }
        }
        
        // Extract location filter - using partial matching to match anywhere in the location string
        if ($request->query->has('location') && $request->query->get('location') !== null) {
            $location = trim($request->query->get('location'));
            if ($location !== '') {
                $filters['location'] = $location;
            }
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
