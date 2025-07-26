<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Connection;

#[AsCommand(
    name: 'app:test-sessions',
    description: 'Test sessions table and database connection',
)]
class TestSessionsCommand extends Command
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln('Testing database connection and sessions table...');

        try {
            // Test basic database connection
            $result = $this->connection->fetchOne('SELECT 1');
            $output->writeln("✓ Database connection working: $result");

            // Check if sessions table exists
            $tables = $this->connection->fetchAllAssociative("SHOW TABLES LIKE 'sessions'");
            if (empty($tables)) {
                $output->writeln("✗ Sessions table does not exist!");
                return Command::FAILURE;
            }
            $output->writeln("✓ Sessions table exists");

            // Check sessions table structure
            $structure = $this->connection->fetchAllAssociative("DESCRIBE sessions");
            $output->writeln("Sessions table structure:");
            foreach ($structure as $column) {
                $output->writeln("  - {$column['Field']}: {$column['Type']}");
            }

            // Check current sessions count
            $count = $this->connection->fetchOne("SELECT COUNT(*) FROM sessions");
            $output->writeln("✓ Current sessions count: $count");

            // Test session creation
            $testSessionId = 'test_' . uniqid();
            $testData = 'test_data_' . time();
            $currentTime = time();
            
            $this->connection->executeStatement(
                "INSERT INTO sessions (sess_id, sess_data, sess_time, sess_lifetime) VALUES (?, ?, ?, ?)",
                [$testSessionId, $testData, $currentTime, 3600]
            );
            $output->writeln("✓ Test session created successfully");

            // Verify test session
            $retrieved = $this->connection->fetchOne(
                "SELECT sess_data FROM sessions WHERE sess_id = ?",
                [$testSessionId]
            );
            
            if ($retrieved === $testData) {
                $output->writeln("✓ Test session data retrieved correctly");
            } else {
                $output->writeln("✗ Test session data mismatch!");
            }

            // Clean up test session
            $this->connection->executeStatement(
                "DELETE FROM sessions WHERE sess_id = ?",
                [$testSessionId]
            );
            $output->writeln("✓ Test session cleaned up");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln("✗ Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
