<?php 

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
use App\Entity\Comment;
use App\Form\CommentType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Repository\ArticleRepository; // pour lister les articles
use Symfony\Component\String\Slugger\SluggerInterface; // pour générer des slugs automatiquement
use Symfony\Component\Security\Http\Attribute\IsGranted; // pour restreindre l'accès aux actions

#[Route('/articles', name: 'articles_')] // This route prefix applies to all methods in this controller
class ArticleController extends AbstractController
{
    #[Route('/list', name: 'list')]
    public function list(ArticleRepository $articleRepository): Response // Injectez ArticleRepository
    {
        // Récupère tous les articles publiés, triés par date de publication décroissante
        $articles = $articleRepository->findBy(
            ['isPublished' => true],
            ['publishedAt' => 'DESC']
        );

        return $this->render('article/list.html.twig', [
            'articles' => $articles
        ]);
    }

    // Route pour afficher un article individuel et gérer les commentaires
    #[Route('/{slug}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        string $slug,
        ArticleRepository $articleRepository,
        Request $request,
        EntityManagerInterface $em): Response
    {
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
            'commentForm' => $form->createView(), // Passe le formulaire à la vue
            'comments' => $article->getComments()->filter(function(Comment $comment) { // Passe seulement les commentaires approuvés
                return $comment->isApproved();
            })->toArray(), // Convertir la collection en tableau
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

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent créer
    #[Route('/create', name: 'create')]
    public function create(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response // Injectez SluggerInterface
    {
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $article = $form->getData();

            // Génération du slug
            if (!$article->getSlug()) { // Générer seulement si pas déjà défini (pour permettre modification manuelle si besoin)
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
                // Si dépublié, réinitialiser la date de publication ou la gérer selon la logique métier
                $article->setPublishedAt(null);
            }
            // viewCount n'est pas encore touché ici

            $em->persist($article);
            $em->flush();

            $this->addFlash('success', 'L\'article a été créé avec succès.');
            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/create.html.twig', [
            'form' => $form->createView()
        ]);
    }
    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent créer
    #[Route('/edit/{slug}', name: 'edit')]
    public function edit(Article $article, Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
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

            $this->addFlash('success', 'L\'article a été modifié avec succès.');
            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article 
        ]);
    }

   
    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent créer
    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(Article $article, EntityManagerInterface $em): RedirectResponse // Injectez l'Article et EntityManagerInterface
    {
        $em->remove($article); // Supprime l'entité
        $em->flush(); // Applique la suppression en base de données

        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('articles_list');
    }
}
