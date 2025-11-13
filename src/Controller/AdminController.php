<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserEditType;
use App\Repository\ArticleRepository;
use App\Repository\CommentRepository;
use App\Repository\ContactRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\CloudinaryUploader;
use Doctrine\ODM\MongoDB\DocumentManager;
use App\Document\ActivityLog;

#[Route('/admin', name: 'admin_')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    #[Route('', name: 'index')]
    public function index(): Response
    {
        return $this->redirectToRoute('admin_dashboard');
    }

    #[Route('/dashboard', name: 'dashboard')]
    public function dashboard(
        Request $request,
        ArticleRepository $articleRepository,
        ContactRepository $contactRepository,
        PaginatorInterface $paginator,
        CloudinaryUploader $cloudinaryUploader
    ): Response {
        // Statistiques pour le dashboard
        $totalArticles = $articleRepository->count([]);
        $publishedArticles = $articleRepository->count(['isPublished' => true]);
        $draftArticles = $articleRepository->count(['isPublished' => false]);
        $unprocessedMessages = $contactRepository->countUnprocessed();
        
        // Articles les plus vus
        $mostViewedArticles = $articleRepository->createQueryBuilder('a')
            ->where('a.viewCount > 0')
            ->orderBy('a.viewCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // Récupérer tous les articles (publiés et non publiés) pour l'admin
        $query = $articleRepository->createQueryBuilder('a')
            ->orderBy('a.createdAt', 'DESC');

        // Pagination
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10 // 10 articles par page
        );

        return $this->render('admin/dashboard.html.twig', [
            'pagination' => $pagination,
            'stats' => [
                'total' => $totalArticles,
                'published' => $publishedArticles,
                'drafts' => $draftArticles,
                'mostViewed' => $mostViewedArticles,
                'unprocessedMessages' => $unprocessedMessages,
            ],
            'cloudinaryUploader' => $cloudinaryUploader // Pass the Cloudinary service to the Twig template
        ]);
    }

    // ==================== GESTION DES UTILISATEURS ====================

    #[Route('/users', name: 'users_list', methods: ['GET'])]
    public function usersList(UserRepository $userRepository, ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
        $users = $userRepository->findAll();

        // Count articles and comments for each user
        $articleCounts = [];
        $commentCounts = [];
        foreach ($users as $user) {
            $articleCounts[$user->getId()] = $articleRepository->count(['author' => $user]);
            $commentCounts[$user->getId()] = $commentRepository->count(['author' => $user]);
        }

        return $this->render('admin/users/list.html.twig', [
            'users' => $users,
            'articleCounts' => $articleCounts,
            'commentCounts' => $commentCounts,
        ]);
    }

    #[Route('/users/new', name: 'users_new', methods: ['GET', 'POST'])]
    public function usersNew(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $form = $this->createForm(UserEditType::class, $user, [
            'is_new' => true,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été créé avec succès.');

            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('admin/users/new.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/edit', name: 'users_edit', methods: ['GET', 'POST'])]
    public function usersEdit(Request $request, User $user, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $form = $this->createForm(UserEditType::class, $user, [
            'is_new' => false,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password only if provided
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            $entityManager->flush();

            $this->addFlash('success', 'L\'utilisateur a été modifié avec succès.');

            return $this->redirectToRoute('admin_users_list');
        }

        return $this->render('admin/users/edit.html.twig', [
            'form' => $form,
            'user' => $user,
        ]);
    }

    #[Route('/users/{id}/delete', name: 'users_delete', methods: ['POST'])]
    public function usersDelete(Request $request, User $user, EntityManagerInterface $entityManager, ArticleRepository $articleRepository, CommentRepository $commentRepository): Response
    {
        // Prevent deletion of current user
        if ($user === $this->getUser()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            return $this->redirectToRoute('admin_users_list');
        }

        // Check if user has articles
        $articleCount = $articleRepository->count(['author' => $user]);
        if ($articleCount > 0) {
            $this->addFlash('error', sprintf(
                'Impossible de supprimer cet utilisateur car il a %d article(s) associé(s). Veuillez d\'abord réassigner ou supprimer ses articles.',
                $articleCount
            ));
            return $this->redirectToRoute('admin_users_list');
        }

        // Check if user has comments
        $commentCount = $commentRepository->count(['author' => $user]);
        if ($commentCount > 0) {
            $this->addFlash('error', sprintf(
                'Impossible de supprimer cet utilisateur car il a %d commentaire(s) associé(s). Veuillez d\'abord supprimer ses commentaires.',
                $commentCount
            ));
            return $this->redirectToRoute('admin_users_list');
        }

        if ($this->isCsrfTokenValid('delete' . $user->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($user);
                $entityManager->flush();

                $this->addFlash('success', 'L\'utilisateur a été supprimé avec succès.');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de la suppression de l\'utilisateur. Il est peut-être lié à d\'autres données.');
            }
        }

        return $this->redirectToRoute('admin_users_list');
    }

    // ==================== LOGS D'ACTIVITÉ ====================

    #[Route('/activity/logs', name: 'activity_logs')]
    public function activityLogs(DocumentManager $documentManager): Response
    {
        $logs = $documentManager->getRepository(ActivityLog::class)
            ->createQueryBuilder()
            ->sort('timestamp', 'DESC')
            ->limit(100)
            ->getQuery()
            ->execute();

        return $this->render('admin/activity_logs.html.twig', [
            'logs' => $logs
        ]);
    }
}