<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DefaultController extends AbstractController
{
    #[Route('/', name: 'app_default_index')]
    public function index()
    {
        return new Response(
            '<html><body>Welcome to the default controller!</body></html>'
        );
    }
}