<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\LoginFormAuthenticator;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // If already logged in, redirect to homepage
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }

        // Get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // Last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
    
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $userPasswordHasher, 
        EntityManagerInterface $entityManager,
        NotificationService $notificationService
    ): Response
    {
        // If already logged in, redirect to homepage
        if ($this->getUser()) {
            return $this->redirectToRoute('app_home');
        }
        
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Check if there's a user with this email already
            $existingUser = $entityManager->getRepository(User::class)->findOneBy([
                'email' => $form->get('email')->getData()
            ]);
            
            if ($existingUser) {
                $this->addFlash('danger', 'This email is already in use. Please use a different email or login with your existing account.');
                return $this->render('security/register.html.twig', [
                    'registrationForm' => $form->createView(),
                ]);
            }
            
            if ($form->isValid()) {
                try {
                    // Encode the plain password
                    $user->setPassword(
                        $userPasswordHasher->hashPassword(
                            $user,
                            $form->get('plainPassword')->getData()
                        )
                    );

                    $entityManager->persist($user);
                    $entityManager->flush();
                    
                    // Send welcome email
                    $notificationService->sendWelcomeEmail($user);

                    // Redirect to login page instead of auto-login
                    $this->addFlash('success', 'Account created successfully! Please log in with your new credentials.');
                    
                    return $this->redirectToRoute('app_login');
                    
                } catch (\Exception $e) {
                    $this->addFlash('danger', 'An error occurred during registration. Please try again.');
                    
                    // Log the error for administrators
                    error_log('Registration error: ' . $e->getMessage());
                }
            }
        }

        return $this->render('security/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
