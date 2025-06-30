<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $hasher, Security $security, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($request->isXmlHttpRequest()) {
            if ($form->isSubmitted() && $form->isValid()) {
                $user->setPassword($hasher->hashPassword($user, $form->get('plainPassword')->getData()));
                $em->persist($user);
                $em->flush();

                return new JsonResponse([
                    'success' => true,
                    'redirect' => $this->generateUrl('app_login')
                ]);
            }

            // Collecte des erreurs
            $errors = [];
            foreach ($form->all() as $field) {
                if (!$field->isValid()) {
                    $errors[$field->getName()] = array_map(fn($e) => $e->getMessage(), iterator_to_array($field->getErrors()));
                }
            }

            return new JsonResponse([
                'success' => false,
                'errors' => $errors,
                'message' => 'Veuillez corriger les erreurs du formulaire.'
            ], 400);
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
        ]);
    }
}
