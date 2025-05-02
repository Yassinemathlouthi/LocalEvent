<?php

namespace App\Controller;

use App\Entity\Attendance;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\AttendanceRepository;
use App\Repository\CategoryRepository;
use App\Repository\EventRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/event')]
class EventController extends AbstractController
{
    #[Route('/', name: 'event_index', methods: ['GET'])]
    public function index(EventRepository $eventRepository): Response
    {
        return $this->render('event/index.html.twig', [
            'events' => $eventRepository->findApprovedEvents(),
        ]);
    }

    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(
        Request $request, 
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        $event = new Event();
        $event->setOrganizer($this->getUser());
        // Ensure event is approved by default
        $event->setIsApproved(true);
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Double check the event is approved
            $event->setIsApproved(true);
            
            // The imageFile is already handled by VichUploader
            $entityManager->persist($event);
            $entityManager->flush();
            
            // Verify that the event was saved with the correct approval status
            $entityManager->refresh($event);
            
            try {
                // Send notification - with error handling
                $notificationService->sendEventCreationConfirmation($event);
            } catch (\Exception $e) {
                // Log the error but don't prevent event creation
                $this->addFlash('warning', 'Event created but confirmation email could not be sent.');
            }

            $this->addFlash('success', 'Your event has been created successfully.');
            return $this->redirectToRoute('event_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(
        Event $event, 
        AttendanceRepository $attendanceRepository,
        EntityManagerInterface $entityManager
    ): Response
    {
        // Add a flash message if event is not approved
        if (!$event->getIsApproved()) {
            // Only admins can approve events
            if ($this->isGranted('ROLE_ADMIN')) {
                $this->addFlash('warning', 'This event is pending approval. As an admin, you can approve it from the admin dashboard.');
            } 
            // Organizers see a different message
            elseif ($this->getUser() && $event->getOrganizer() === $this->getUser()) {
                $this->addFlash('warning', 'Your event is pending approval by an administrator.');
            }
            // Regular users see this message
            else {
                $this->addFlash('warning', 'This event is pending approval by an administrator and cannot be joined until approved.');
            }
        }
        
        // Check if current user is attending
        $isAttending = false;
        if ($this->getUser()) {
            $isAttending = $attendanceRepository->isAttending($this->getUser(), $event);
        }
        
        // Get attendees
        $attendees = $attendanceRepository->findEventAttendances($event);
        
        // Check if the event has already passed
        $eventPassed = false;
        $now = new \DateTime();
        if ($event->getDate() < $now) {
            $eventPassed = true;
            
            // If user is attending and event has passed, set a flash message
            if ($isAttending && $this->getUser()) {
                // Check if we've already marked as missed
                $attendance = $attendanceRepository->findOneBy([
                    'user' => $this->getUser(),
                    'event' => $event,
                    'status' => Attendance::STATUS_JOINED
                ]);
                
                if ($attendance) {
                    $attendance->setStatus(Attendance::STATUS_MISSED);
                    $entityManager->flush();
                    $this->addFlash('info', 'This event has already passed. Your attendance has been marked as missed.');
                }
            }
        }
        
        // Pass approval status to template
        $canJoin = $event->getIsApproved() && !$eventPassed;
        
        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isAttending' => $isAttending,
            'attendees' => $attendees,
            'eventPassed' => $eventPassed,
            'canJoin' => $canJoin
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request, 
        Event $event, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check that current user is the organizer or an admin
        if ($this->getUser() !== $event->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only edit your own events.');
        }
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // The imageFile is already handled by VichUploader
            
            // Reset approval if the event is modified by the organizer and not an admin
            if ($this->getUser() === $event->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
                $event->setIsApproved(false);
                $this->addFlash('info', 'Your event has been updated and is pending approval again.');
            } else {
                $this->addFlash('success', 'Event updated successfully.');
            }
            
            $entityManager->flush();
            
            return $this->redirectToRoute('event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/edit.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'event_delete', methods: ['POST'])]
    public function delete(
        Request $request, 
        Event $event, 
        EntityManagerInterface $entityManager
    ): Response
    {
        // Check that current user is the organizer or an admin
        if ($this->getUser() !== $event->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('You can only delete your own events.');
        }
        
        if ($this->isCsrfTokenValid('delete'.$event->getId(), $request->request->get('_token'))) {
            $entityManager->remove($event);
            $entityManager->flush();
            $this->addFlash('success', 'Event deleted successfully.');
        }

        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('admin_events', [], Response::HTTP_SEE_OTHER);
        }
        
        return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{id}/join', name: 'event_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        Event $event, 
        Request $request,
        EntityManagerInterface $entityManager,
        AttendanceRepository $attendanceRepository,
        NotificationService $notificationService
    ): Response
    {
        // Check if event is approved
        if (!$event->getIsApproved()) {
            $this->addFlash('error', 'You cannot join an event that has not been approved.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
        
        // Check if the current user is the organizer
        if ($event->getOrganizer() === $this->getUser()) {
            $this->addFlash('error', 'You cannot join an event you have organized. You are the host of this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
        
        // Check if the event has already passed
        $now = new \DateTime();
        if ($event->getDate() < $now) {
            $this->addFlash('error', 'This event has already passed. You can no longer join it.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
        
        // Check if user is already attending
        if ($attendanceRepository->isAttending($this->getUser(), $event)) {
            $this->addFlash('info', 'You are already attending this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
        
        if ($this->isCsrfTokenValid('join'.$event->getId(), $request->request->get('_token'))) {
            try {
                $attendance = new Attendance();
                $attendance->setUser($this->getUser());
                $attendance->setEvent($event);
                $attendance->setStatus(Attendance::STATUS_JOINED);
                
                $entityManager->persist($attendance);
                $entityManager->flush();
                
                try {
                    // Send notification
                    $notificationService->sendEventJoinNotification($attendance);
                } catch (\Exception $e) {
                    // Log the error but don't prevent joining
                    $this->addFlash('warning', 'Joined successfully but notification email could not be sent.');
                }
                
                $this->addFlash('success', 'You have successfully joined the event!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'An error occurred while joining the event. Please try again.');
                // Log the error
                error_log('Error joining event: ' . $e->getMessage());
            }
        } else {
            $this->addFlash('error', 'Invalid token. Please try again.');
        }
        
        // Get the referer to redirect back to the page the user was on
        $referer = $request->headers->get('referer');
        
        // If coming from event list, redirect back there
        if ($referer && strpos($referer, 'event/') !== false && !strpos($referer, 'event/'.$event->getId())) {
            return $this->redirect($referer);
        }
        
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
    
    #[Route('/{id}/leave', name: 'event_leave', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function leave(
        Event $event, 
        Request $request,
        EntityManagerInterface $entityManager,
        AttendanceRepository $attendanceRepository
    ): Response
    {
        if ($this->isCsrfTokenValid('leave'.$event->getId(), $request->request->get('_token'))) {
            $attendance = $attendanceRepository->findOneBy([
                'user' => $this->getUser(),
                'event' => $event,
                'status' => Attendance::STATUS_JOINED
            ]);
            
            if ($attendance) {
                // If the event has already passed
                $now = new \DateTime();
                if ($event->getDate() < $now) {
                    $this->addFlash('info', 'This event has already passed. Your attendance has been marked as missed.');
                    $attendance->setStatus(Attendance::STATUS_MISSED);
                } else {
                    $attendance->setStatus(Attendance::STATUS_CANCELLED);
                    $this->addFlash('success', 'You have cancelled your attendance for this event.');
                }
                
                try {
                    $entityManager->flush();
                } catch (\Exception $e) {
                    $this->addFlash('error', 'An error occurred while updating your attendance status.');
                    error_log('Error leaving event: ' . $e->getMessage());
                }
            } else {
                $this->addFlash('info', 'You are not attending this event.');
            }
        } else {
            $this->addFlash('error', 'Invalid token. Please try again.');
        }
        
        // Check if we need to redirect back to the attending dashboard
        $referer = $request->headers->get('referer');
        if ($referer && strpos($referer, 'dashboard/attending') !== false) {
            return $this->redirectToRoute('app_attending');
        }
        
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
    
    #[Route('/by-category/{id}', name: 'event_by_category', methods: ['GET'])]
    public function byCategory(
        Request $request,
        CategoryRepository $categoryRepository,
        EventRepository $eventRepository,
        int $id
    ): Response
    {
        $category = $categoryRepository->find($id);
        
        if (!$category) {
            throw $this->createNotFoundException('Category not found');
        }
        
        $events = $eventRepository->findByCategory($category);
        
        return $this->render('event/by_category.html.twig', [
            'category' => $category,
            'events' => $events,
        ]);
    }

    #[Route('/{id}/quick-approve', name: 'event_quick_approve', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function quickApprove(
        Event $event, 
        Request $request,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        if ($this->isCsrfTokenValid('approve'.$event->getId(), $request->request->get('_token'))) {
            $event->setIsApproved(true);
            $entityManager->flush();
            
            try {
                // Send notification to organizer
                $notificationService->sendEventApprovalNotification($event);
            } catch (\Exception $e) {
                // Log the error but don't prevent approval
                $this->addFlash('warning', 'Event approved but notification email could not be sent.');
                error_log('Error sending approval notification: ' . $e->getMessage());
            }
            
            $this->addFlash('success', 'Event has been approved.');
        } else {
            $this->addFlash('error', 'Invalid token. Please try again.');
        }
        
        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
}
