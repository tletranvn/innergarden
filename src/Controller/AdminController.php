<?php

namespace App\Controller;

use App\Repository\ArticleRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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
        PaginatorInterface $paginator
    ): Response {
        // Statistiques pour le dashboard
        $totalArticles = $articleRepository->count([]);
        $publishedArticles = $articleRepository->count(['isPublished' => true]);
        $draftArticles = $articleRepository->count(['isPublished' => false]);
        
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
                'mostViewed' => $mostViewedArticles
            ]
        ]);
    }
}
