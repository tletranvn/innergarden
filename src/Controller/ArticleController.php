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
use App\Document\Photo;
use Knp\Component\Pager\PaginatorInterface;


#[Route('/articles', name: 'articles_')]
class ArticleController extends AbstractController
{
    // les routes spécifiques (create, edit, delete) AVANT la route générique {slug}.

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
            // L'entité $article est hydratée par le formulaire.
            // VichUploaderBundle mettra à jour l'imageFile (et via cela, imageName, imageSize, etc. si configuré)
            // dès que l'entité est persistée et flushée.

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
                          // À ce stade, VichUploader a déplacé l'image et mis à jour imageName (et autres si configuré) sur $article.

            // NOUVEAU : Enregistrement des métadonnées de l'image dans MongoDB
            // On vérifie si un fichier a été soumis ET si VichUploader a bien mis à jour le nom de l'image sur l'entité Article.
            // Si $article->getImageName() n'est pas null, cela signifie que VichUploader a traité l'upload.
            if ($article->getImageName() !== null) {
                $photo = new Photo();
                $photo->setFilename($article->getImageName());

                // CORRECTION ICI : Utilisez les getters des propriétés de l'entité Article
                // qui ont été remplies par VichUploaderBundle.
                $photo->setOriginalFilename($article->getImageOriginalName());
                $photo->setMimeType($article->getImageMimeType());
                $photo->setSize($article->getImageSize());

                $photo->setRelatedArticleId((string)$article->getId()); // Lie la photo à l'ID de l'article MySQL

                $documentManager->persist($photo);
                $documentManager->flush(); // Persiste le document Photo dans MongoDB
            } else {
                // NOUVEAU : Message d'avertissement si le nom de l'image est null après soumission.
                // Cela indique un problème avec l'upload ou la configuration de VichUploader.
                $this->addFlash('warning', 'L\'image a été soumise, mais ses métadonnées n\'ont pas pu être enregistrées dans MongoDB. Veuillez vérifier la configuration de VichUploader et l\'entité Article.');
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
                          // À ce stade, VichUploader a traité l'image si une nouvelle a été soumise.

            // NOUVEAU : Mise à jour/création/suppression des métadonnées dans MongoDB pour l'image de l'article
            // Si une nouvelle image a été soumise, $article->getImageName() ne sera pas null après le flush.
            if ($article->getImageName() !== null) {
                // Tente de trouver un document Photo existant lié à cet article
                $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);

                if (!$photo) {
                    // Si aucun document Photo n'existe, on en crée un nouveau
                    $photo = new Photo();
                    $photo->setRelatedArticleId((string)$article->getId());
                }

                // NOUVEAU : Met à jour les métadonnées de la photo
                $photo->setFilename($article->getImageName());
                // CORRECTION ICI : Utilisez les getters des propriétés de l'entité Article
                // qui ont été remplies par VichUploaderBundle.
                $photo->setOriginalFilename($article->getImageOriginalName());
                $photo->setMimeType($article->getImageMimeType());
                $photo->setSize($article->getImageSize());

                $documentManager->persist($photo);
                $documentManager->flush();
            } elseif ($form->get('imageFile')->getNormData() === null && $article->getImageName() === null) {
                // NOUVEAU : Logique pour supprimer la photo si elle est retirée du formulaire d'édition.
                // Cela signifie que le champ imageFile était vide ET que l'article n'a plus de nom d'image.
                $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);
                if ($photo) {
                    $documentManager->remove($photo);
                    $documentManager->flush();
                }
            }


            $this->addFlash('success', 'L\'article a été modifié avec succès.');
            return $this->redirectToRoute('articles_list');
        }

        return $this->render('article/show.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent supprimer
    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(
        Article $article,
        Request $request,
        EntityManagerInterface $em,
        DocumentManager $documentManager
    ): RedirectResponse {
        // vérification CSRF
        // Le jeton CSRF est envoyé dans le formulaire de suppression (généralement un bouton de type "submit" avec un champ caché _token)
        // et vérifié ici
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $article->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }
        // Supprimer l'article de la base de données
        // L'EntityManager gère la suppression de l'article
        // et VichUploaderBundle s'occupe de la suppression de l'image associée.
        $em->remove($article);
        $em->flush();

        // NOUVEAU : Supprimer les métadonnées de la photo de MongoDB
        $photo = $documentManager->getRepository(Photo::class)->findOneBy(['relatedArticleId' => (string)$article->getId()]);
        if ($photo) {
            $documentManager->remove($photo);
            $documentManager->flush();
        }

        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('articles_list');
    }

    // Route pour lister les articles publiés avec pagination
    // Utilisation de KnpPaginatorBundle pour la pagination
    #[Route('/list', name: 'list')]
    public function list(
        Request $request, // Injection de la requête HTTP
        ArticleRepository $articleRepository,
        PaginatorInterface $paginator // Injection du service de pagination
    ): Response {
        // préparer la requête (au lieu de récupérer tous les résultats d'un coup)
        $query = $articleRepository->createQueryBuilder('a')
            ->where('a.isPublished = :published')
            ->setParameter('published', true)
            ->orderBy('a.publishedAt', 'DESC');

        // utiliser le service de pagination
        $pagination = $paginator->paginate(
            $query, // la requête
            $request->query->getInt('page', 1), // numéro de page depuis l'URL, par défaut 1
            9 // nombre d'articles par page
        );

        return $this->render('article/list.html.twig', [
            'pagination' => $pagination // envoyer la pagination à la vue
        ]);
    }
    // route générique doit être définie APRÈS les routes spécifiques comme /create, /edit/{slug}, /delete/{slug}
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