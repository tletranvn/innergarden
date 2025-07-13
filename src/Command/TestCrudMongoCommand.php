<?php

namespace App\Command;

use App\Document\Photo;
use App\Entity\Article;
use App\Repository\ArticleRepository;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-crud-mongo',
    description: 'Test CRUD operations on MongoDB Photo documents',
)]
class TestCrudMongoCommand extends Command
{
    public function __construct(
        private DocumentManager $documentManager,
        private ArticleRepository $articleRepository
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Test 1: Vérifier les photos existantes
        $photos = $this->documentManager->getRepository(Photo::class)->findAll();
        $io->info('Photos trouvées dans MongoDB: ' . count($photos));
        
        foreach ($photos as $photo) {
            $io->writeln(sprintf(
                '- Photo ID: %s, Article ID: %s, Filename: %s',
                $photo->getId(),
                $photo->getRelatedArticleId(),
                $photo->getFilename()
            ));
        }

        // Test 2: Vérifier les articles avec images
        $articlesWithImages = $this->articleRepository->createQueryBuilder('a')
            ->where('a.imageName IS NOT NULL')
            ->getQuery()
            ->getResult();

        $io->info('Articles avec images trouvés: ' . count($articlesWithImages));

        foreach ($articlesWithImages as $article) {
            $photo = $this->documentManager->getRepository(Photo::class)
                ->findOneBy(['relatedArticleId' => (string)$article->getId()]);
            
            $mongoStatus = $photo ? '✅ Présent dans MongoDB' : '❌ Manquant dans MongoDB';
            
            $io->writeln(sprintf(
                '- Article ID: %d, Slug: %s, Image: %s | %s',
                $article->getId(),
                $article->getSlug(),
                $article->getImageName(),
                $mongoStatus
            ));
        }

        $io->success('Test CRUD MongoDB terminé !');

        return Command::SUCCESS;
    }
}
