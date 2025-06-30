<?php

namespace App\DataFixtures;

use App\Entity\Article;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\String\Slugger\SluggerInterface;

class ArticleFixtures extends Fixture implements DependentFixtureInterface
{
    private SluggerInterface $slugger;

    public function __construct(SluggerInterface $slugger)
    {
        $this->slugger = $slugger;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $users = [
            $this->getReference('user_admin', \App\Entity\User::class),
            $this->getReference('user_0', \App\Entity\User::class),
            $this->getReference('user_1', \App\Entity\User::class),
        ];

        $categories = [
            $this->getReference('category_0', \App\Entity\Category::class),
            $this->getReference('category_1', \App\Entity\Category::class),
            $this->getReference('category_2', \App\Entity\Category::class),
            $this->getReference('category_3', \App\Entity\Category::class),
        ];

        for ($i = 0; $i < 20; $i++) {
            $article = new Article();
            $title = $faker->sentence(mt_rand(5, 10));
            $content = $faker->paragraphs(mt_rand(5, 15), true);

            $article->setTitle($title);
            $article->setSlug($this->slugger->slug($title . '-' . uniqid())->lower());
            $article->setContent($content);
            $article->setExcerpt($faker->paragraph(2));
            $article->setIsPublished(true);
            $article->setViewCount($faker->numberBetween(0, 1000));
            $article->setAuthor($faker->randomElement($users));
            $article->setCategory($faker->randomElement($categories));

            // Dates cohérentes avec Faker et DateTimeImmutable
            $createdAt = \DateTimeImmutable::createFromMutable($faker->dateTimeBetween('-6 months', '-2 months'));
            $article->setCreatedAt($createdAt);

            $updatedAt = $faker->dateTimeBetween($createdAt->format('Y-m-d H:i:s'), 'now');
            $article->setUpdatedAt(\DateTimeImmutable::createFromMutable($updatedAt));

            $publishedAt = $faker->dateTimeBetween('-2 months', 'now');
            $article->setPublishedAt(\DateTimeImmutable::createFromMutable($publishedAt));

            $manager->persist($article);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CategoryFixtures::class,
        ];
    }
}
