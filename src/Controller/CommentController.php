<?php

namespace App\Controller;

use App\Entity\Article;
use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class CommentController extends AbstractController
{
    #[Route('/articles/{id}/comment', name: 'app_comment_new', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function new(Request $request, Article $article, EntityManagerInterface $em): JsonResponse
    {
        $comment = (new Comment())
            ->setArticle($article)
            ->setAuthor($this->getUser());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Commentaire ajouté avec succès !',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getComment(),
                    'authorPseudo' => $comment->getAuthor()->getPseudo(),
                    'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                ]
            ], Response::HTTP_CREATED);
        }

        $errors = [];
        foreach ($form->getErrors(true) as $error) {
            $path = $error->getCause()?->getPropertyPath() ?? 'global';
            $errors[$path][] = $error->getMessage();
        }

        return new JsonResponse([
            'success' => false,
            'message' => 'Erreurs de validation.',
            'errors' => $errors
        ], Response::HTTP_BAD_REQUEST);
    }
}
