<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class HomeControllerTest extends WebTestCase
{
    public function testIndex(): void
    {
        // Crée le client Symfony pour tester les routes
        $client = static::createClient();
        $client->request('GET', '/');

        // Vérifie que la réponse HTTP est OK (200)
        $this->assertResponseIsSuccessful();
    }
}
