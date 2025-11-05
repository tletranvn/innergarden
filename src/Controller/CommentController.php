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
        // Vérifier que l'article est publié
        if (!$article->isPublished()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Impossible de commenter un article non publié.',
            ], Response::HTTP_FORBIDDEN);
        }

        $comment = (new Comment())
            ->setArticle($article)
            ->setAuthor($this->getUser())
            ->setCreatedAt(new \DateTimeImmutable())
            ->setIsApproved(true); // Publication directe sans modération

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($comment);
            $em->flush();

            return new JsonResponse([
                'success' => true,
                'message' => 'Commentaire publié avec succès.',
                'comment' => [
                    'id' => $comment->getId(),
                    'content' => $comment->getComment(),
                    'authorPseudo' => $comment->getAuthor()->getPseudo(),
                    'createdAt' => $comment->getCreatedAt()->format('d/m/Y H:i'),
                    'isApproved' => $comment->isApproved()
                ]
            ], Response::HTTP_CREATED);
        }

        // Gestion des erreurs de validation
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

    #[Route('/articles/{slug}/comment', name: 'app_comment_new_by_slug', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function newBySlug(Request $request, string $slug, EntityManagerInterface $em): JsonResponse
    {
        // Récupérer l'article par slug
        $article = $em->getRepository(Article::class)->findOneBy(['slug' => $slug]);
        
        if (!$article) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Article non trouvé.',
            ], Response::HTTP_NOT_FOUND);
        }

        // Utiliser la même logique que la méthode principale
        return $this->new($request, $article, $em);
    }
}
