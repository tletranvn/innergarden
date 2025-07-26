<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class DebugController extends AbstractController
{
    #[Route('/debug/registration', name: 'debug_registration', methods: ['POST'])]
    public function debugRegistration(Request $request): JsonResponse
    {
        $data = [
            'method' => $request->getMethod(),
            'content_type' => $request->headers->get('Content-Type'),
            'post_data' => $request->request->all(),
            'csrf_token' => $request->request->get('registration_form')['_token'] ?? 'No CSRF token found',
            'session_id' => $request->getSession()->getId(),
            'app_secret_configured' => !empty($_ENV['APP_SECRET']),
        ];

        return new JsonResponse($data);
    }
}
