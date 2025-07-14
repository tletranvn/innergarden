<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Form\ContactType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function index(
        Request $request, 
        EntityManagerInterface $em, 
        MailerInterface $mailer
    ): Response {
        $contact = new Contact();
        $form = $this->createForm(ContactType::class, $contact);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Sauvegarder le message de contact en base
            $em->persist($contact);
            $em->flush();

            // Envoyer l'email si l'option est cochée
            //if ($contact->isSendEmail()) {
            //    try {
            //        $email = (new Email())
            //            ->from($contact->getEmail())
            //            ->to('tletranvn@gmail.com')
            //            ->subject('Nouveau message de contact - Inner Garden')
            //            ->html($this->renderView('emails/contact.html.twig', [
            //                'contact' => $contact
            //            ]));
            //        $mailer->send($email);
            //        
            //       $this->addFlash('success', 'Votre message a été envoyé avec succès ! Un email a également été expédié.');
            //    } catch (\Exception $e) {
            //        // En cas d'erreur d'envoi d'email, on sauvegarde quand même le message
            //        $this->addFlash('warning', 'Votre message a été enregistré, mais l\'envoi par email a échoué. Nous vous recontacterons bientôt.');
            //    }
            //} else {
            //    $this->addFlash('success', 'Votre message a été envoyé avec succès ! Nous vous recontacterons bientôt.');
            //}

            // Redirection pour éviter la re-soumission
            return $this->redirectToRoute('app_contact');
        }

        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
