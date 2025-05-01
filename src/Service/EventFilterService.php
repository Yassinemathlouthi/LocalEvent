<?php

namespace App\Service;

use App\Entity\Category;
use App\Repository\EventRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;

class EventFilterService
{
    private EventRepository $eventRepository;
    private PaginatorInterface $paginator;

    public function __construct(
        EventRepository $eventRepository,
        PaginatorInterface $paginator
    ) {
        $this->eventRepository = $eventRepository;
        $this->paginator = $paginator;
    }

    /**
     * Filter events by category, location, and date
     */
    public function filterEvents(Request $request, int $page = 1, int $limit = 10)
    {
        $category = null;
        $location = null;
        $date = null;

        // Get filter parameters from request
        if ($request->query->has('category') && $request->query->get('category') > 0) {
            $category = $request->query->get('category');
        }

        if ($request->query->has('location') && !empty($request->query->get('location'))) {
            $location = $request->query->get('location');
        }

        if ($request->query->has('date') && !empty($request->query->get('date'))) {
            try {
                $date = new \DateTime($request->query->get('date'));
            } catch (\Exception $e) {
                $date = null;
            }
        }

        // Build query with filters
        $queryBuilder = $this->eventRepository->findByFilters(
            $category instanceof Category ? $category : ($category ? new Category($category) : null),
            $location,
            $date
        );

        // Return paginated results
        return $this->paginator->paginate(
            $queryBuilder,
            $page,
            $limit
        );
    }
}
