<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Repository\AttendanceRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/dashboard')]
#[IsGranted('ROLE_USER')]
class UserDashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        EventRepository $eventRepository, 
        AttendanceRepository $attendanceRepository
    ): Response
    {
        // Get user's events
        $user = $this->getUser();
        $myEvents = $eventRepository->findBy(['organizer' => $user], ['date' => 'ASC']);
        
        // Get events the user is attending
        $attendances = $attendanceRepository->findUserAttendances($user);
        
        return $this->render('user_dashboard/index.html.twig', [
            'myEvents' => $myEvents,
            'attendances' => $attendances,
        ]);
    }
    
    #[Route('/profile', name: 'app_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ProfileType::class, $user);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            
            $this->addFlash('success', 'Your profile has been updated.');
            return $this->redirectToRoute('app_profile');
        }
        
        return $this->render('user_dashboard/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
    
    #[Route('/my-events', name: 'app_my_events')]
    public function myEvents(EventRepository $eventRepository): Response
    {
        $user = $this->getUser();
        $myEvents = $eventRepository->findBy(['organizer' => $user], ['date' => 'ASC']);
        
        return $this->render('user_dashboard/my_events.html.twig', [
            'events' => $myEvents,
        ]);
    }
    
    #[Route('/attending', name: 'app_attending')]
    public function attending(AttendanceRepository $attendanceRepository): Response
    {
        $user = $this->getUser();
        $attendances = $attendanceRepository->findUserAttendances($user);
        
        return $this->render('user_dashboard/attending.html.twig', [
            'attendances' => $attendances,
        ]);
    }
}
