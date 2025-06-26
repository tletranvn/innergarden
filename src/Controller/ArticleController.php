<?php 

namespace App\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;

#[Route('/articles', name: 'articles_')] // This route prefix applies to all methods in this controller
class ArticleController extends AbstractController
{
    #[Route('/', name: 'list')]
    public function list()
    {
       
        return $this->render('article/list.html.twig');
    }

    #[Route('/create', name: 'create')]
    public function create()
    {
        
        return $this->render('article/create.html.twig');
    }

    #[Route('/edit/{id}', name: 'edit')]
    public function edit(int $id)
    {
        
        return $this->render('article/edit.html.twig');
    }

    #[Route('/delete/{id}', name: 'delete')]
    public function delete(): RedirectResponse
    {
        $this->addFlash('success', 'L\'article a été supprimé avec succès.');
        return $this->redirectToRoute('articles_list');
    }
}
