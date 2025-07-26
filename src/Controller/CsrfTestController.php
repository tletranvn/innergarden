<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class CsrfTestController extends AbstractController
{
    #[Route('/test-csrf', name: 'test_csrf')]
    public function testCsrf(Request $request): JsonResponse
    {
        $session = $request->getSession();
        $csrfTokenManager = $this->container->get('security.csrf.token_manager');
        
        return new JsonResponse([
            'session_started' => $session->isStarted(),
            'session_id' => $session->getId(),
            'csrf_token' => $csrfTokenManager->getToken('registration_form')->getValue(),
            'app_secret_configured' => !empty($_ENV['APP_SECRET']) || !empty($_SERVER['APP_SECRET']),
            'environment' => $this->getParameter('kernel.environment'),
        ]);
    }
}
