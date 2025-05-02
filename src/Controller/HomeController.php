<?php

namespace App\Controller;

use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Service\EventFilterService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        EventRepository $eventRepository,
        CategoryRepository $categoryRepository,
        EventFilterService $eventFilterService,
        PaginatorInterface $paginator
    ): Response
    {
        // Get filter data
        $categories = $categoryRepository->findAll();
        
        // Get page parameter
        $page = $request->query->getInt('page', 1);
        
        // Get filtered events (without pagination limits)
        $filteredEvents = $eventFilterService->getFilteredEvents($request);
        
        // Paginate the results
        $events = $paginator->paginate(
            $filteredEvents,
            $page,
            9 // 9 events per page
        );
        
        // Get upcoming events for sidebar
        $upcomingEvents = $eventRepository->findUpcomingEvents(5);
        
        return $this->render('home/index.html.twig', [
            'categories' => $categories,
            'events' => $events,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    #[Route('/about', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('home/about.html.twig');
    }
}
