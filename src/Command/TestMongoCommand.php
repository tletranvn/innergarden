<?php
namespace App\Command;

use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestMongoCommand extends Command
{
    private $mongoClient;

    public function __construct(Client $mongoClient)
    {
        parent::__construct();
        $this->mongoClient = $mongoClient;
    }

    protected function configure()
    {
        $this->setName('app:test-mongo');
        $this->setDescription('Test connection to MongoDB');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $databases = $this->mongoClient->listDatabases();
            $output->writeln('MongoDB connection OK. Databases found:');
            foreach ($databases as $db) {
                $output->writeln(' - ' . $db->getName());
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('MongoDB connection failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
