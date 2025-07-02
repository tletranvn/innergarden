<?php

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\ArticleRepository;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\Photo; // <<< ASSUREZ-VOUS QUE CETTE LIGNE EST BIEN PRÉSENTE !

#[Route('/articles', name: 'articles_')]
class ArticleController extends AbstractController
{
    // Solution au problème 2 : Déplacez les routes spécifiques (create, edit, delete) AVANT la route générique {slug}.

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent créer
    #[Route('/create', name: 'create')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article = $form->getData();

            // Génération du slug
            if (!$article->getSlug()) {
                $article->setSlug($slugger->slug($article->getTitle())->lower());
            }

            // Gestion des dates
            if (null === $article->getCreatedAt()) {
                $article->setCreatedAt(new \DateTimeImmutable());
            }
            $article->setUpdatedAt(new \DateTimeImmutable());
            if ($article->isPublished() && null === $article->getPublishedAt()) {
                $article->setPublishedAt(new \DateTimeImmutable());
            } elseif (!$article->isPublished() && null !== $article->getPublishedAt()) {
                $article->setPublishedAt(null);
            }

            $em->persist($article);
            $em->flush(); // IMPORTANT : Flush avant d'accéder à l'ID de l'article

            // Enregistrement manuel des métadonnées dans MongoDB
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $photo = new Photo(); // <<< Plus de ligne rouge si 'use App\Document\Photo;' est présent
                $photo->setFilename($article->getImageName());
                $photo->setOriginalFilename($imageFile->getClientOriginalName());
                $photo->setMimeType($imageFile->getMimeType());
                $photo->setSize($imageFile->getSize());
                $photo->setRelatedArticleId((string)$article->getId());

                $documentManager->persist($photo);
                $documentManager->flush();
            }

            $this->addFlash('success', 'L\'article a été créé avec succès.');
            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent éditer
    #[Route('/edit/{slug}', name: 'edit')]
    public function edit(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response {
        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug (si vous autorisez la modification du titre)
            if (!$article->getSlug()) {
                $article->setSlug($slugger->slug($article->getTitle())->lower());
            }
            $article->setUpdatedAt(new \DateTimeImmutable());
            if ($article->isPublished() && null === $article->getPublishedAt()) {
                $article->setPublishedAt(new \DateTimeImmutable());
            } elseif (!$article->isPublished() && null !== $article->getPublishedAt()) {
                $article->setPublishedAt(null);
            }

            $em->flush(); // Pas besoin de persist car l'entité est déjà gérée par l'EntityManager

            // Mise à jour/création des métadonnées dans MongoDB pour l'image de l'article
            $imageFile = $form->get('imageFile')->getData();

            if ($imageFile) {
                $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);

                if (!$photo) {
                    $photo = new Photo();
                    $photo->setRelatedArticleId((string)$article->getId());
                }

                $photo->setFilename($article->getImageName());
                $photo->setOriginalFilename($imageFile->getClientOriginalName());
                $photo->setMimeType($imageFile->getMimeType());
                $photo->setSize($imageFile->getSize());

                $documentManager->persist($photo);
                $documentManager->flush();
            } elseif ($form->get('imageFile')->getNormData() === null && $article->getImageName() !== null) {
                $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);
                if ($photo) {
                    $documentManager->remove($photo);
                    $documentManager->flush();
                }
            }


            $this->addFlash('success', 'L\'article a été modifié avec succès.');
            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent supprimer
    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(
        Article $article,
        EntityManagerInterface $em,
        DocumentManager $documentManager
    ): RedirectResponse {
        $em->remove($article);
        $em->flush();

        // Supprimer les métadonnées de la photo de MongoDB
        $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);
        if ($photo) {
            $documentManager->remove($photo);
            $documentManager->flush();
        }

        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('articles_list');
    }

    #[Route('/list', name: 'list')]
    public function list(ArticleRepository $articleRepository): Response
    {
        $articles = $articleRepository->findBy(
            ['isPublished' => true],
            ['publishedAt' => 'DESC']
        );

        return $this->render('article/list.html.twig', [
            'articles' => $articles
        ]);
    }

    // Solution au problème 2 : Cette route générique doit être définie APRÈS les routes spécifiques comme /create, /edit/{slug}, /delete/{slug}
    #[Route('/{slug}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        string $slug,
        ArticleRepository $articleRepository,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $article = $articleRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Aucun article trouvé avec ce slug.');
        }

        if (!$article->isPublished()) {
            throw $this->createNotFoundException('L\'article existe mais n\'est pas publié.');
        }


        // --- Logique du formulaire de commentaire ---
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // S'assurer que l'utilisateur est connecté pour commenter
            if (!$this->getUser()) {
                $this->addFlash('danger', 'Vous devez être connecté pour poster un commentaire.');
                return $this->redirectToRoute('app_login'); // Rediriger vers la page de connexion
            }

            $comment->setArticle($article);
            $comment->setAuthor($this->getUser()); // Associe l'utilisateur connecté comme auteur
            $comment->setCreatedAt(new \DateTimeImmutable());
            $comment->setIsApproved(false); // Par défaut, le commentaire n'est pas approuvé (pour la modération)

            $em->persist($comment);
            $em->flush();

            $this->addFlash('success', 'Votre commentaire a été soumis avec succès et est en attente de modération.');

            // Rediriger vers l'article pour éviter la soumission multiple du formulaire
            return $this->redirectToRoute('articles_show', ['slug' => $article->getSlug()]);
        }

        // incrémenter le compteur de vues
        $article->setViewCount($article->getViewCount() + 1);
        $em->flush();

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView(),
            'comments' => $article->getComments()->filter(function(Comment $comment) {
                return $comment->isApproved();
            })->toArray(),
        ]);
    }


    // Une méthode pour publier les derniers articles sur la Homepage
    public function latestArticles(ArticleRepository $articleRepository, int $limit = 5): Response
    {
        // Récupère les derniers articles publiés, triés par date de publication décroissante
        $latestArticles = $articleRepository->findBy(
            ['isPublished' => true], // Filtrer uniquement les articles publiés
            ['publishedAt' => 'DESC'], // Trier par la date de publication
            $limit // Limite le nombre d'articles
        );

        return $this->render('partials/_latest_articles.html.twig', [
            'latest_articles' => $latestArticles,
        ]);
    }
}