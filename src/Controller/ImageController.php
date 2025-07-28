<?php

namespace App\Controller;

use App\Document\Photo;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ImageController extends AbstractController
{
    #[Route('/images/article/{articleId}', name: 'app_image_article')]
    public function serveArticleImage(string $articleId, DocumentManager $documentManager): Response
    {
        // Find the photo document for this article
        $photo = $documentManager->getRepository(Photo::class)
            ->findOneBy(['relatedArticleId' => $articleId]);

        if (!$photo || !$photo->getImageData()) {
            // Return a 404 or redirect to placeholder
            throw $this->createNotFoundException('Image not found');
        }

        // Decode the base64 image data
        $imageData = base64_decode($photo->getImageData());
        
        // Create response with proper headers
        $response = new Response($imageData);
        $response->headers->set('Content-Type', $photo->getMimeType() ?: 'image/jpeg');
        $response->headers->set('Content-Length', (string) strlen($imageData));
        $response->headers->set('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
        
        return $response;
    }
}
