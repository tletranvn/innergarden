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

        $articlesToPublish = $this->articleRepository->createQueryBuilder('a')
            ->where('a.publishedAt <= :now')
            ->andWhere('a.isPublished = false OR a.isPublished IS NULL')
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();

        $count = count($articlesToPublish);

        foreach ($articlesToPublish as $article) {
            $article->setIsPublished(true);
        }

        $this->entityManager->flush();

        if ($count > 0) {
            $io->success("$count article(s) publié(s).");
        } else {
            $io->note("Aucun article à publier pour le moment.");
        }

        return Command::SUCCESS;
    }
}
