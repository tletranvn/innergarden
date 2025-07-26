<?php

namespace App\Controller;

use App\Form\LoginForm;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login', methods: ['GET', 'POST'])]
    public function login(Request $request, AuthenticationUtils $authenticationUtils, LoggerInterface $logger): Response
    {
        // Ensure session is started for CSRF protection (same as registration)
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        // Create the login form (same pattern as registration)
        $loginForm = $this->createForm(LoginForm::class);
        
        // Handle form submission for debugging
        $loginForm->handleRequest($request);
        
        if ($loginForm->isSubmitted()) {
            $logger->info('Login form submitted', [
                'method' => $request->getMethod(),
                'isValid' => $loginForm->isValid(),
                'session_id' => $session->getId(),
            ]);
        }
        
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $logger->error('Authentication error: ' . $error->getMessage());
        }

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'loginForm' => $loginForm,
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
