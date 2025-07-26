<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security; // Keep this import
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface; // Good practice to keep for logging errors

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $hasher,
        EntityManagerInterface $em,
        Security $security, // Inject Security service for auto-login
        LoggerInterface $logger // Keep this injected for error logging
    ): Response {
        // Ensure session is started for CSRF protection
        $session = $request->getSession();
        if (!$session->isStarted()) {
            $session->start();
        }
        
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Encode the plain password
            $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));

            try {
                // Persist the user to the database
                $em->persist($user);
                $em->flush();

                // --- START: Auto-login the user immediately after successful registration ---
                // The Security helper can be used to log in the user directly.
                // This typically uses the default authenticator configured for the firewall.
                $security->login($user, 'form_login', 'main'); // 'form_login' is the authenticator type, 'main' is the firewall name

                // Add a flash message for user feedback
                $this->addFlash('success', 'Votre compte a été créé avec succès et vous êtes maintenant connecté !');

                // Redirect to the home page
                return $this->redirectToRoute('app_home');
                // --- END: Auto-login and redirect ---

            } catch (\Exception $e) {
                // Log the exception for debugging purposes
                $logger->error('Registration database error: ' . $e->getMessage(), ['exception' => $e]);

                // Add a flash message for internal server errors
                $this->addFlash('error', 'Une erreur est survenue lors de l\'enregistrement. Veuillez réessayer.');

                // Render the form again, preserving user input and displaying general errors
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                ], new Response(null, Response::HTTP_INTERNAL_SERVER_ERROR)); // Return 500 status for server error
            }
        } elseif ($form->isSubmitted()) {
            // Form was submitted but has validation errors
            $logger->info('Registration form validation failed', [
                'session_id' => $request->getSession()->getId(),
                'csrf_token_submitted' => $request->request->get('registration_form')['_token'] ?? 'none',
                'csrf_valid' => $form->get('_token') ? $form->get('_token')->isValid() : 'no_csrf_field'
            ]);
            
            // Log all form errors for debugging in Heroku logs
            foreach ($form->getErrors(true) as $error) {
                $errorMessage = $error->getMessage();
                $logger->error('Registration form error: ' . $errorMessage, [
                    'field' => $error->getOrigin() ? $error->getOrigin()->getName() : 'form',
                    'submitted_data' => array_intersect_key($request->request->all(), array_flip(['registration_form']))
                ]);
                
                // Handle CSRF token errors specifically
                if (strpos($errorMessage, 'CSRF token') !== false || strpos($errorMessage, 'token is invalid') !== false) {
                    $this->addFlash('error', 'Token de sécurité invalide. Veuillez réessayer.');
                } else {
                    $this->addFlash('warning', $errorMessage);
                }
            }
            
            // Check for specific field errors and add user-friendly messages
            if ($form->get('email')->getErrors()->count() > 0) {
                $this->addFlash('error', 'Il y a un problème avec l\'adresse email.');
            }
            if ($form->get('pseudo')->getErrors()->count() > 0) {
                $this->addFlash('error', 'Il y a un problème avec le pseudo.');
            }
            if ($form->get('plainPassword')->getErrors()->count() > 0) {
                $this->addFlash('error', 'Il y a un problème avec le mot de passe.');
            }
            if ($form->get('agreeTerms')->getErrors()->count() > 0) {
                $this->addFlash('error', 'Vous devez accepter les conditions d\'utilisation.');
            }
            
            // Return with 422 status to indicate validation error
            return $this->render('registration/register.html.twig', [
                'registrationForm' => $form,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // If it's a GET request (initial page load) or a POST request with invalid data,
        // render the registration form. Twig will automatically display validation errors.
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}