<?php 

namespace App\Controller;

use App\Entity\Article;
use App\Form\ArticleType;
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
        $articles = $articleRepository->findAll(); // Récupère tous les articles

        return $this->render('article/list.html.twig', [
            'articles' => $articles // Passez les articles au template
        ]);
    }

    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Article $article): Response
    {
        return $this->render('article/show.html.twig', [
            'article' => $article,
        ]);
    }
    // Une méthode pour publier les derniers articles sur la Homepage
    public function latestArticles(ArticleRepository $articleRepository, int $limit = 5): Response
    {
        $latestArticles = $articleRepository->findBy(
            ['isPublished' => true], // Filtre pour les articles publiés
            ['publishedAt' => 'DESC'], // Tri par date de publication décroissante
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
    #[Route('/edit/{id}', name: 'edit')]
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
    #[Route('/delete/{id}', name: 'delete')]
    public function delete(Article $article, EntityManagerInterface $em): RedirectResponse // Injectez l'Article et EntityManagerInterface
    {
        $em->remove($article); // Supprime l'entité
        $em->flush(); // Applique la suppression en base de données

        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('articles_list');
    }
}
