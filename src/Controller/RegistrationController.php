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
        }

        // If it's a GET request (initial page load) or a POST request with invalid data,
        // render the registration form. Twig will automatically display validation errors.
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}