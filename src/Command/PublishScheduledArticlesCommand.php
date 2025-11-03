<?php

namespace App\Command;

use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:publish-scheduled-articles',
    description: 'Publie les articles dont la date de publication est atteinte.',
)]
class PublishScheduledArticlesCommand extends Command
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now = new \DateTimeImmutable();

        // Debug: Afficher la date actuelle
        $io->info("Date actuelle : " . $now->format('Y-m-d H:i:s'));

        // Debug: Afficher tous les articles avec leur publishedAt
        $allArticles = $this->articleRepository->createQueryBuilder('a')
            ->select('a.id', 'a.title', 'a.publishedAt', 'a.isPublished')
            ->getQuery()
            ->getArrayResult();

        if (!empty($allArticles)) {
            $io->section("Tous les articles dans la base :");
            $io->table(
                ['ID', 'Title', 'PublishedAt', 'IsPublished'],
                array_map(function($article) {
                    return [
                        $article['id'],
                        $article['title'],
                        $article['publishedAt'] ? $article['publishedAt']->format('Y-m-d H:i:s') : 'NULL',
                        $article['isPublished'] ? 'true' : 'false'
                    ];
                }, $allArticles)
            );
        }

        $articlesToPublish = $this->articleRepository->createQueryBuilder('a')
            ->where('a.publishedAt <= :now')
            ->andWhere('a.isPublished = false OR a.isPublished IS NULL')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = count($articlesToPublish);

        if ($count > 0) {
            $io->section("Articles à publier :");
            foreach ($articlesToPublish as $article) {
                $io->writeln(sprintf(
                    "- [%d] %s (publishedAt: %s)",
                    $article->getId(),
                    $article->getTitle(),
                    $article->getPublishedAt() ? $article->getPublishedAt()->format('Y-m-d H:i:s') : 'NULL'
                ));
                $article->setIsPublished(true);
            }

            $this->entityManager->flush();
            $io->success("$count article(s) publié(s).");
        } else {
            $io->note("Aucun article à publier pour le moment.");
        }

        return Command::SUCCESS;
    }
}
