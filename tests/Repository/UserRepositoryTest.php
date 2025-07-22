<?php
namespace App\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

class UserRepositoryTest extends KernelTestCase
{
    private ?EntityManagerInterface $entityManager = null;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testInsertAndFindUser(): void
    {
        // Création d’un nouvel utilisateur
        $user = new User();
        $user->setPseudo('TestUser');
        $user->setEmail('test@example.com');
        $user->setPassword('dummy'); // mot de passe non encodé ici pour test

        // Persiste en base
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        // Récupère via le repository
        $repo = $this->entityManager->getRepository(User::class);
        $foundUser = $repo->findOneBy(['email' => 'test@example.com']);

        // Assertion
        $this->assertNotNull($foundUser);
        $this->assertEquals('test@example.com', $foundUser->getEmail());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
        $this->entityManager = null;
    }
}
