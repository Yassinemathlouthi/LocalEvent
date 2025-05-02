<?php

namespace App\Controller;

use App\Form\ProfileType;
use App\Form\ChangePasswordType;
use App\Repository\AttendanceRepository;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Security\Core\User\UserPasswordHasherInterface;

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
    
    #[Route('/upload-profile-picture', name: 'app_upload_profile_picture', methods: ['POST'])]
    public function uploadProfilePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        // CSRF token validation
        if (!$this->isCsrfTokenValid('upload-profile-picture', $request->request->get('token'))) {
            $this->addFlash('danger', 'Invalid CSRF token.');
            return $this->redirectToRoute('app_profile');
        }
        
        $user = $this->getUser();
        $file = $request->files->get('profilePicture');
        
        if (!$file) {
            $this->addFlash('danger', 'No image file was uploaded.');
            return $this->redirectToRoute('app_profile');
        }
        
        // Validate file type
        $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        if (!in_array($file->getMimeType(), $allowedMimeTypes)) {
            $this->addFlash('danger', 'Invalid file type. Only JPG and PNG images are allowed.');
            return $this->redirectToRoute('app_profile');
        }
        
        // Validate file size (2MB max)
        if ($file->getSize() > 2 * 1024 * 1024) {
            $this->addFlash('danger', 'Image file is too large. Maximum size is 2MB.');
            return $this->redirectToRoute('app_profile');
        }
        
        // Generate unique filename
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->sanitizeFilename($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        
        try {
            // Create uploads/profile directory if it doesn't exist
            $uploadsDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/profile';
            if (!file_exists($uploadsDirectory)) {
                mkdir($uploadsDirectory, 0777, true);
            }
            
            // Move the file
            $file->move($uploadsDirectory, $newFilename);
            
            // Delete old profile picture if exists
            if ($user->getProfilePicture()) {
                $oldFilePath = $uploadsDirectory . '/' . $user->getProfilePicture();
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }
            
            // Update user entity
            $user->setProfilePicture($newFilename);
            $entityManager->flush();
            
            $this->addFlash('success', 'Profile picture updated successfully!');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'An error occurred while uploading the profile picture: ' . $e->getMessage());
        }
        
        return $this->redirectToRoute('app_profile');
    }
    
    /**
     * Sanitize a filename to make it safe for filesystem
     */
    private function sanitizeFilename(string $filename): string
    {
        // Convert to lowercase
        $filename = strtolower($filename);
        // Replace accented characters
        $filename = preg_replace('/[áàâäãåæ]/u', 'a', $filename);
        $filename = preg_replace('/[éèêë]/u', 'e', $filename);
        $filename = preg_replace('/[íìîï]/u', 'i', $filename);
        $filename = preg_replace('/[óòôöõø]/u', 'o', $filename);
        $filename = preg_replace('/[úùûü]/u', 'u', $filename);
        $filename = preg_replace('/[ýÿ]/u', 'y', $filename);
        $filename = preg_replace('/[ç]/u', 'c', $filename);
        $filename = preg_replace('/[ñ]/u', 'n', $filename);
        // Replace spaces and special chars with underscore
        $filename = preg_replace('/[^a-z0-9_]/', '_', $filename);
        // Replace multiple underscores with a single one
        $filename = preg_replace('/_+/', '_', $filename);
        // Trim underscores from beginning and end
        return trim($filename, '_');
    }
    
    #[Route('/change-password', name: 'app_change_password')]
    public function changePassword(
        Request $request, 
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response
    {
        $form = $this->createForm(ChangePasswordType::class);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $data = $form->getData();
            
            // Verify current password
            if (!$passwordHasher->isPasswordValid($user, $data['currentPassword'])) {
                $this->addFlash('danger', 'Current password is incorrect.');
                return $this->redirectToRoute('app_change_password');
            }
            
            // Hash the new password
            $hashedPassword = $passwordHasher->hashPassword(
                $user,
                $data['newPassword']
            );
            
            // Update user password
            $user->setPassword($hashedPassword);
            $entityManager->flush();
            
            $this->addFlash('success', 'Your password has been updated successfully.');
            return $this->redirectToRoute('app_profile');
        }
        
        return $this->render('security/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
