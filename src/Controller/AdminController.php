<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Event;
use App\Entity\User;
use App\Form\CategoryType;
use App\Repository\AttendanceRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('/', name: 'admin_dashboard')]
    public function index(
        EventRepository $eventRepository,
        UserRepository $userRepository,
        AttendanceRepository $attendanceRepository
    ): Response
    {
        // Get statistics for dashboard
        $pendingEventsCount = count($eventRepository->findPendingEvents());
        $totalEventsCount = count($eventRepository->findAll());
        $totalUsersCount = count($userRepository->findAll());
        $attendanceStats = $attendanceRepository->getStatistics();
        $latestUsers = $userRepository->findLatestUsers(5);
        
        return $this->render('admin/dashboard.html.twig', [
            'pendingEventsCount' => $pendingEventsCount,
            'totalEventsCount' => $totalEventsCount,
            'totalUsersCount' => $totalUsersCount,
            'attendanceStats' => $attendanceStats,
            'latestUsers' => $latestUsers,
        ]);
    }
    
    #[Route('/events', name: 'admin_events')]
    public function events(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();
        
        return $this->render('admin/events.html.twig', [
            'events' => $events,
        ]);
    }
    
    #[Route('/events/pending', name: 'admin_events_pending')]
    public function pendingEvents(EventRepository $eventRepository): Response
    {
        $pendingEvents = $eventRepository->findPendingEvents();
        
        return $this->render('admin/pending_events.html.twig', [
            'pendingEvents' => $pendingEvents,
        ]);
    }
    
    #[Route('/event/{id}/approve', name: 'admin_event_approve', methods: ['POST'])]
    public function approveEvent(
        Request $request,
        Event $event,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        if ($this->isCsrfTokenValid('approve'.$event->getId(), $request->request->get('_token'))) {
            $event->setIsApproved(true);
            $entityManager->flush();
            
            // Send notification to organizer
            $notificationService->sendEventApprovalNotification($event);
            
            $this->addFlash('success', 'Event has been approved.');
        }
        
        return $this->redirectToRoute('admin_events_pending');
    }
    
    #[Route('/users', name: 'admin_users')]
    public function users(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();
        
        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }
    
    #[Route('/user/{id}/role', name: 'admin_user_toggle_role', methods: ['POST'])]
    public function toggleUserRole(User $user, Request $request, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('role'.$user->getId(), $request->request->get('_token'))) {
            $roles = $user->getRoles();
            
            // Toggle ROLE_ADMIN
            if (in_array('ROLE_ADMIN', $roles)) {
                $roles = array_diff($roles, ['ROLE_ADMIN']);
                $message = 'Admin role has been removed from the user.';
            } else {
                $roles[] = 'ROLE_ADMIN';
                $message = 'User has been promoted to admin.';
            }
            
            $user->setRoles($roles);
            $entityManager->flush();
            
            $this->addFlash('success', $message);
        }
        
        return $this->redirectToRoute('admin_users');
    }
    
    #[Route('/categories', name: 'admin_categories')]
    public function categories(CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        
        return $this->render('admin/categories.html.twig', [
            'categories' => $categories,
        ]);
    }
    
    #[Route('/category/new', name: 'admin_category_new', methods: ['GET', 'POST'])]
    public function newCategory(Request $request, EntityManagerInterface $entityManager): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Category created successfully.');
            return $this->redirectToRoute('admin_categories');
        }
        
        return $this->render('admin/category_form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }
    
    #[Route('/category/{id}/edit', name: 'admin_category_edit', methods: ['GET', 'POST'])]
    public function editCategory(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response
    {
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Category updated successfully.');
            return $this->redirectToRoute('admin_categories');
        }
        
        return $this->render('admin/category_form.html.twig', [
            'form' => $form->createView(),
            'category' => $category,
        ]);
    }
    
    #[Route('/category/{id}/delete', name: 'admin_category_delete', methods: ['POST'])]
    public function deleteCategory(
        Request $request,
        Category $category,
        EntityManagerInterface $entityManager
    ): Response
    {
        if ($this->isCsrfTokenValid('delete'.$category->getId(), $request->request->get('_token'))) {
            // Check if category has events
            if (count($category->getEvents()) > 0) {
                $this->addFlash('error', 'Cannot delete category with associated events.');
                return $this->redirectToRoute('admin_categories');
            }
            
            $entityManager->remove($category);
            $entityManager->flush();
            
            $this->addFlash('success', 'Category deleted successfully.');
        }
        
        return $this->redirectToRoute('admin_categories');
    }
}
