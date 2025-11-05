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
use Knp\Component\Pager\PaginatorInterface;
use App\Service\CloudinaryUploader;
use App\Service\ActivityLogger;



#[Route('/articles', name: 'articles_')]
class ArticleController extends AbstractController
{
    public function __construct()
    {
        // Enable PHP logging for debugging
        ini_set('log_errors', 1);
        ini_set('error_log', '/tmp/article_debug.log');
        error_log("DEBUG: ArticleController constructor called");
    }
    
    // les routes spécifiques (create, edit, delete) AVANT la route générique {slug}.

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent créer
    #[Route('/create', name: 'create')]
    public function create(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        CloudinaryUploader $cloudinaryUploader,
        ActivityLogger $activityLogger
    ): Response {
        // Enable PHP logging for debugging
        ini_set('log_errors', 1);
        ini_set('error_log', '/tmp/article_debug.log');
        
        error_log("DEBUG: ArticleController::create method called");
        error_log("DEBUG: Request method: " . $request->getMethod());
        error_log("DEBUG: Request has files: " . ($request->files->count() > 0 ? 'true' : 'false'));
        
        $article = new Article();
        $form = $this->createForm(ArticleType::class, $article);

        $form->handleRequest($request);
        error_log("DEBUG: Form submitted: " . ($form->isSubmitted() ? 'true' : 'false'));
        
        if ($form->isSubmitted()) {
            error_log("DEBUG: Form valid: " . ($form->isValid() ? 'true' : 'false'));
            error_log("DEBUG: Request files: " . json_encode($request->files->all()));
            
            if (!$form->isValid()) {
                error_log("DEBUG: Form errors: " . (string) $form->getErrors(true));
            }
        }
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug
            if (!$article->getSlug()) {
                $article->setSlug($slugger->slug($article->getTitle())->lower());
            }

            // Gestion des dates
            if (null === $article->getCreatedAt()) {
                $article->setCreatedAt(new \DateTimeImmutable());
            }
            $article->setUpdatedAt(new \DateTimeImmutable());

            // Logique de publication :
            // 1. Si isPublished = true ET publishedAt = NULL → publier immédiatement
            if ($article->isPublished() && null === $article->getPublishedAt()) {
                $article->setPublishedAt(new \DateTimeImmutable());
            }
            // 2. Si isPublished = false ET publishedAt est dans le futur → article programmé
            // 3. Si isPublished = false ET publishedAt = NULL → brouillon non programmé
            // (Pas besoin de code supplémentaire, le formulaire gère publishedAt)

            try {
                // Persist article first (to get ID for MongoDB reference)
                $em->persist($article);
                error_log("DEBUG: About to flush article to MySQL");
                $em->flush();
                error_log("DEBUG: Article flushed successfully, ID: " . $article->getId());

                // Handle Cloudinary image upload AFTER persisting article
                $imageFile = $form->get('imageFile')->getData();

                if ($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    // Upload to Cloudinary
                    $result = $cloudinaryUploader->upload($imageFile);

                    // Store only the public_id in MySQL
                    $article->setImagePublicId($result['public_id']);
                    $em->flush();
                }

                // Log l'activité dans MongoDB (sans bloquer si ça échoue)
                $activityLogger->logArticleCreate($article, $this->getUser());

                $this->addFlash('success', 'L\'article a été créé avec succès.');
                return $this->redirectToRoute('articles_show', ['slug' => $article->getSlug()]);

            } catch (\Exception $e) {
                error_log("ERROR: Article creation failed: " . $e->getMessage());
                error_log("ERROR: Stack trace: " . $e->getTraceAsString());
                $this->addFlash('error', 'Erreur lors de la création de l\'article: ' . $e->getMessage());
                // Re-render le formulaire avec les données
            }
        }

        error_log("DEBUG: Rendering create form (either initial load or after error)");

        return $this->render('article/create.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent éditer
    #[Route('/edit/{slug}', name: 'edit')]
    public function edit(
        string $slug,
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        ArticleRepository $articleRepository,
        CloudinaryUploader $cloudinaryUploader,
        ActivityLogger $activityLogger
    ): Response {
        error_log("DEBUG: ArticleController::edit method called for slug: " . $slug);
        error_log("DEBUG: Request method in edit: " . $request->getMethod());
        
        // Récupération explicite de l'article par slug
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé avec le slug: ' . $slug);
        }
        
        // Sauvegarder l'état initial de l'image pour détecter les suppressions
        $originalImagePublicId = $article->getImagePublicId();

        $form = $this->createForm(ArticleType::class, $article);
        $form->handleRequest($request);

        error_log("DEBUG: Form submitted: " . ($form->isSubmitted() ? 'true' : 'false'));

        if ($form->isSubmitted()) {
            error_log("DEBUG: Form valid: " . ($form->isValid() ? 'true' : 'false'));

            if (!$form->isValid()) {
                error_log("DEBUG: Form errors: " . (string) $form->getErrors(true, true));
                // Log each field error
                foreach ($form->getErrors(true, true) as $error) {
                    error_log("DEBUG: Form error: " . $error->getMessage());
                }
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du slug (si vous autorisez la modification du titre)
            if (!$article->getSlug()) {
                $article->setSlug($slugger->slug($article->getTitle())->lower());
            }
            $article->setUpdatedAt(new \DateTimeImmutable());

            // Logique de publication :
            // 1. Si isPublished = true ET publishedAt = NULL → publier immédiatement
            if ($article->isPublished() && null === $article->getPublishedAt()) {
                $article->setPublishedAt(new \DateTimeImmutable());
            }
            // 2. Si isPublished = false ET publishedAt est dans le futur → article programmé
            // 3. Si isPublished = false ET publishedAt = NULL → brouillon non programmé
            // (Pas besoin de code supplémentaire, le formulaire gère publishedAt)

            // Gérer l'upload d'une nouvelle image si présente
            $imageFile = $form->get('imageFile')->getData();

            try {
                if ($imageFile instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                    // Supprimer l'ancienne image de Cloudinary si elle existe
                    if ($originalImagePublicId) {
                        // TODO: Implémenter la suppression Cloudinary
                        // $cloudinaryUploader->delete($originalImagePublicId);
                    }

                    // Upload nouvelle image vers Cloudinary
                    $result = $cloudinaryUploader->upload($imageFile);

                    // Mettre à jour le public_id dans MySQL
                    $article->setImagePublicId($result['public_id']);
                }

                // Sauvegarder toutes les modifications MySQL en une seule fois
                $em->flush();

                // Log l'édition dans MongoDB
                $activityLogger->logArticleEdit($article, $this->getUser());

                $this->addFlash('success', 'L\'article a été modifié avec succès.');
                return $this->redirectToRoute('articles_show', ['slug' => $article->getSlug()]);

            } catch (\Exception $e) {
                error_log("ERROR: Article edit failed: " . $e->getMessage());
                error_log("ERROR: Stack trace: " . $e->getTraceAsString());
                $this->addFlash('error', 'Erreur lors de la modification de l\'article: ' . $e->getMessage());
                // Re-render le formulaire avec les données
            }
        }

        error_log("DEBUG: Rendering edit form (either initial load or after error)");

        return $this->render('article/edit.html.twig', [
            'form' => $form->createView(),
            'article' => $article
        ]);
    }

    #[IsGranted('ROLE_ADMIN')] // Seuls les utilisateurs avec le rôle ADMIN peuvent supprimer
    #[Route('/delete/{slug}', name: 'delete')]
    public function delete(
        string $slug,
        Request $request,
        EntityManagerInterface $em,
        ArticleRepository $articleRepository,
        ActivityLogger $activityLogger
    ): RedirectResponse {
        // Récupération explicite de l'article par slug
        $article = $articleRepository->findOneBy(['slug' => $slug]);
        
        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé avec le slug: ' . $slug);
        }
        // vérification CSRF
        // Le jeton CSRF est envoyé dans le formulaire de suppression (généralement un bouton de type "submit" avec un champ caché _token)
        // et vérifié ici
        $submittedToken = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $article->getId(), $submittedToken)) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        // Log la suppression AVANT de supprimer l'article (pour avoir accès aux données)
        $activityLogger->logArticleDelete($article, $this->getUser());

        // Supprimer l'article de la base de données MySQL
        try {
            $em->remove($article);
            $em->flush();
        } catch (\Exception $e) {
            error_log("ERROR: Failed to delete article: " . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de la suppression de l\'article: ' . $e->getMessage());
            return $this->redirectToRoute('articles_list');
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
        PaginatorInterface $paginator, // Injection du service de pagination
        CloudinaryUploader $cloudinaryUploader // Inject CloudinaryUploader for image URLs
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
            'pagination' => $pagination, // envoyer la pagination à la vue
            'cloudinaryUploader' => $cloudinaryUploader // Pass CloudinaryUploader to template
        ]);
    }

    // route générique doit être définie APRÈS les routes spécifiques comme /create, /edit/{slug}, /delete/{slug}
    #[Route('/{slug}', name: 'show', methods: ['GET', 'POST'])]
    public function show(
        string $slug,
        ArticleRepository $articleRepository,
        Request $request,
        EntityManagerInterface $em,
        CloudinaryUploader $cloudinaryUploader,
        ActivityLogger $activityLogger
    ): Response {
        error_log("DEBUG: ArticleController::show method called for slug: " . $slug);
        error_log("DEBUG: Request method in show: " . $request->getMethod());
        
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

            // Log la création du commentaire
            $activityLogger->logCommentCreate($comment, $this->getUser());

            $this->addFlash('success', 'Votre commentaire a été soumis avec succès et est en attente de modération.');

            // Rediriger vers l'article pour éviter la soumission multiple du formulaire
            return $this->redirectToRoute('articles_show', ['slug' => $article->getSlug()]);
        }

        // incrémenter le compteur de vues
        $article->setViewCount($article->getViewCount() + 1);
        $em->flush();

        // Log la vue dans MongoDB (asynchrone, ne bloque pas)
        $activityLogger->logArticleView($article, $this->getUser());

        return $this->render('article/show.html.twig', [
            'article' => $article,
            'commentForm' => $form->createView(),
            'comments' => $article->getComments()->filter(function(Comment $comment) {
                return $comment->isApproved();
            })->toArray(),
            'cloudinaryUploader' => $cloudinaryUploader // *** IMPORTANT: Pass the service to the Twig template ***
        ]);
    }

    // Une méthode pour publier les derniers articles sur la Homepage
    public function latestArticles(ArticleRepository $articleRepository, CloudinaryUploader $cloudinaryUploader, int $limit = 6): Response
    {
        // Récupère les derniers articles publiés, triés par date de publication décroissante
        $latestArticles = $articleRepository->findBy(
            ['isPublished' => true], // Filtrer uniquement les articles publiés
            ['publishedAt' => 'DESC'], // Trier par la date de publication
            $limit // Limite le nombre d'articles
        );

        return $this->render('partials/_latest_articles.html.twig', [
            'latest_articles' => $latestArticles,
            'cloudinaryUploader' => $cloudinaryUploader
        ]);
    }
}