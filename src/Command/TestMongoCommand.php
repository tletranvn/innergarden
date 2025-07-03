<?php
namespace App\Command;

use App\Document\Photo;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:test-mongo')]
class TestMongoCommand extends Command
{
    public function __construct(private DocumentManager $dm)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $photo = new Photo();
        $photo->setFilename('test.jpg');
        $photo->setOriginalFilename('original.jpg');
        $photo->setMimeType('image/jpeg');
        $photo->setSize(123456);
        $photo->setRelatedArticleId('123');

        $this->dm->persist($photo);
        $this->dm->flush();

        $output->writeln('Photo insérée avec succès');
        return Command::SUCCESS;
    }
}
