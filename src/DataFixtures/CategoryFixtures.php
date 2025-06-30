<?php

namespace App\DataFixtures;

use App\Entity\Category;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\String\Slugger\SluggerInterface; // Pour générer le slug

class CategoryFixtures extends Fixture
{
    public function __construct(
        private SluggerInterface $slugger
    ) {}

    public function load(ObjectManager $manager): void
    {
        $categoryNames = [
            'Jardinage',
            'Voyage Nature',
            'Bien-être',
            'Développement Personnel',
        ];

        foreach ($categoryNames as $key => $name) {
            $category = new Category();
            $category->setName($name);
            $category->setSlug($this->slugger->slug($name)->lower()); // Génère un slug
            $manager->persist($category);
            $this->addReference('category_' . $key, $category); // Stocke la référence
        }

        $manager->flush();
    }
}