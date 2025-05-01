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
        
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($event);
            $entityManager->flush();
            
            // Send notification
            $notificationService->sendEventCreationConfirmation($event);

            $this->addFlash('success', 'Your event has been created and is pending approval.');
            return $this->redirectToRoute('app_dashboard', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('event/new.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(
        Event $event, 
        AttendanceRepository $attendanceRepository
    ): Response
    {
        // Check if event is approved or if current user is the organizer
        if (!$event->isIsApproved() && 
            (!$this->getUser() || $event->getOrganizer() !== $this->getUser()) && 
            !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException('This event is pending approval and can only be viewed by the organizer or an admin.');
        }
        
        // Check if current user is attending
        $isAttending = false;
        if ($this->getUser()) {
            $isAttending = $attendanceRepository->isAttending($this->getUser(), $event);
        }
        
        // Get attendees
        $attendees = $attendanceRepository->findEventAttendances($event);
        
        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isAttending' => $isAttending,
            'attendees' => $attendees,
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
            // Reset approval if the event is modified by the organizer and not an admin
            if ($this->getUser() !== $event->getOrganizer() && !$this->isGranted('ROLE_ADMIN')) {
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
        if (!$event->isIsApproved()) {
            throw $this->createAccessDeniedException('You cannot join an event that has not been approved.');
        }
        
        // Check if user is already attending
        if ($attendanceRepository->isAttending($this->getUser(), $event)) {
            $this->addFlash('info', 'You are already attending this event.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
        }
        
        if ($this->isCsrfTokenValid('join'.$event->getId(), $request->request->get('_token'))) {
            $attendance = new Attendance();
            $attendance->setUser($this->getUser());
            $attendance->setEvent($event);
            $attendance->setStatus(Attendance::STATUS_JOINED);
            
            $entityManager->persist($attendance);
            $entityManager->flush();
            
            // Send notification
            $notificationService->sendEventJoinNotification($attendance);
            
            $this->addFlash('success', 'You have successfully joined the event!');
        }
        
        return $this->redirectToRoute('event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
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
                $attendance->setStatus(Attendance::STATUS_CANCELLED);
                $entityManager->flush();
                
                $this->addFlash('success', 'You have cancelled your attendance for this event.');
            }
        }
        
        return $this->redirectToRoute('event_show', ['id' => $event->getId()], Response::HTTP_SEE_OTHER);
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
}
