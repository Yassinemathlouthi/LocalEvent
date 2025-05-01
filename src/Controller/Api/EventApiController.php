<?php

namespace App\Controller\Api;

use App\Entity\Attendance;
use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class EventApiController extends AbstractController
{
    #[Route('/events', name: 'api_events_list', methods: ['GET'])]
    public function index(EventRepository $eventRepository, SerializerInterface $serializer): JsonResponse
    {
        $events = $eventRepository->findApprovedEvents();
        
        $json = $serializer->serialize($events, 'json', [
            'groups' => ['event:read'],
            'json_encode_options' => JSON_PRETTY_PRINT
        ]);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
    
    #[Route('/event/{id}', name: 'api_event_show', methods: ['GET'])]
    public function show(Event $event, SerializerInterface $serializer): JsonResponse
    {
        // Check if event is approved
        if (!$event->isIsApproved() && 
            (!$this->getUser() || $event->getOrganizer() !== $this->getUser()) && 
            !$this->isGranted('ROLE_ADMIN')
        ) {
            return new JsonResponse(['error' => 'Event not found or not approved'], Response::HTTP_NOT_FOUND);
        }
        
        $json = $serializer->serialize($event, 'json', [
            'groups' => ['event:read', 'event:item:get'],
            'json_encode_options' => JSON_PRETTY_PRINT
        ]);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
    
    #[Route('/event', name: 'api_event_create', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request, 
        SerializerInterface $serializer,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): JsonResponse
    {
        try {
            $event = $serializer->deserialize(
                $request->getContent(),
                Event::class,
                'json',
                ['groups' => ['event:write']]
            );
            
            $event->setOrganizer($this->getUser());
            $event->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($event);
            $entityManager->flush();
            
            // Send notification
            $notificationService->sendEventCreationConfirmation($event);
            
            return new JsonResponse(
                $serializer->serialize($event, 'json', ['groups' => ['event:read']]),
                Response::HTTP_CREATED,
                [],
                true
            );
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
    
    #[Route('/event/{id}/join', name: 'api_event_join', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function join(
        Event $event,
        EntityManagerInterface $entityManager,
        NotificationService $notificationService,
        SerializerInterface $serializer
    ): JsonResponse
    {
        // Check if event is approved
        if (!$event->isIsApproved()) {
            return new JsonResponse(['error' => 'Event is not approved yet'], Response::HTTP_BAD_REQUEST);
        }
        
        // Check if user is already attending
        $attendance = $entityManager->getRepository(Attendance::class)->findOneBy([
            'user' => $this->getUser(),
            'event' => $event,
        ]);
        
        if ($attendance && $attendance->getStatus() === Attendance::STATUS_JOINED) {
            return new JsonResponse(['message' => 'You are already attending this event'], Response::HTTP_OK);
        }
        
        if ($attendance) {
            // If previously cancelled, just update status
            $attendance->setStatus(Attendance::STATUS_JOINED);
            $attendance->setJoinedAt(new \DateTimeImmutable());
        } else {
            // Create new attendance
            $attendance = new Attendance();
            $attendance->setUser($this->getUser());
            $attendance->setEvent($event);
            $attendance->setStatus(Attendance::STATUS_JOINED);
            $entityManager->persist($attendance);
        }
        
        $entityManager->flush();
        
        // Send notification
        $notificationService->sendEventJoinNotification($attendance);
        
        return new JsonResponse(
            $serializer->serialize($attendance, 'json'),
            Response::HTTP_CREATED,
            [],
            true
        );
    }
    
    #[Route('/user/{id}/events', name: 'api_user_events', methods: ['GET'])]
    public function userEvents(
        User $user,
        EventRepository $eventRepository,
        SerializerInterface $serializer
    ): JsonResponse
    {
        // Only return approved events unless request is for current user or by admin
        if ($this->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
            $events = $eventRepository->findBy([
                'organizer' => $user,
                'isApproved' => true
            ]);
        } else {
            $events = $eventRepository->findBy(['organizer' => $user]);
        }
        
        $json = $serializer->serialize($events, 'json', [
            'groups' => ['event:read'],
            'json_encode_options' => JSON_PRETTY_PRINT
        ]);
        
        return new JsonResponse($json, Response::HTTP_OK, [], true);
    }
}
