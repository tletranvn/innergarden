<?php

namespace App\Command;

use App\Repository\ArticleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-cloudinary-public-ids',
    description: 'Remove file extensions from Cloudinary public_ids in the database',
)]
class FixCloudinaryImagePublicIdCommand extends Command
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be changed without actually changing it')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $dryRun = $input->getOption('dry-run');

        if ($dryRun) {
            $io->warning('Running in DRY-RUN mode - no changes will be made');
        }

        $articles = $this->articleRepository->findAll();
        $updatedCount = 0;
        $unchangedCount = 0;

        $io->info(sprintf('Found %d articles to process', count($articles)));

        foreach ($articles as $article) {
            $publicId = $article->getImagePublicId();

            if (!$publicId) {
                $io->text(sprintf('Article #%d "%s": No image', $article->getId(), $article->getTitle()));
                $unchangedCount++;
                continue;
            }

            // Remove file extension if present
            $originalPublicId = $publicId;
            $newPublicId = preg_replace('/\.(jpg|jpeg|png|gif|webp)$/i', '', $publicId);

            if ($originalPublicId !== $newPublicId) {
                $io->text(sprintf(
                    'Article #%d "%s": %s -> %s',
                    $article->getId(),
                    $article->getTitle(),
                    $originalPublicId,
                    $newPublicId
                ));

                if (!$dryRun) {
                    $article->setImagePublicId($newPublicId);
                    $updatedCount++;
                } else {
                    $updatedCount++;
                }
            } else {
                $io->text(sprintf('Article #%d "%s": Already correct (%s)', $article->getId(), $article->getTitle(), $publicId));
                $unchangedCount++;
            }
        }

        if (!$dryRun && $updatedCount > 0) {
            $this->entityManager->flush();
            $io->success(sprintf('Successfully updated %d articles', $updatedCount));
        } elseif ($dryRun && $updatedCount > 0) {
            $io->note(sprintf('Would update %d articles (use without --dry-run to apply changes)', $updatedCount));
        }

        $io->info(sprintf('Summary: %d updated, %d unchanged', $updatedCount, $unchangedCount));

        return Command::SUCCESS;
    }
}
