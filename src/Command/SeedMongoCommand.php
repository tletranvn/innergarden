<?php

namespace App\Command;

use MongoDB\Client;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:seed-mongo',
    description: 'Insert test data into MongoDB for the innergarden project',
)]
class SeedMongoCommand extends Command
{
    public function __construct(private Client $mongoClient)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Connect to the innergarden_mongodb database
            $database = $this->mongoClient->selectDatabase('innergarden_mongodb');
            
            $io->info('Connected to MongoDB database: innergarden_mongodb');

            // Clear existing data
            $database->articles->deleteMany([]);
            $database->comments->deleteMany([]);
            $database->users->deleteMany([]);
            
            $io->info('Cleared existing test data');

            // Insert articles
            $articles = [
                [
                    '_id' => new \MongoDB\BSON\ObjectId(),
                    'title' => 'Introduction au jardinage urbain',
                    'content' => 'Le jardinage urbain est une pratique qui gagne en popularité. Dans cet article, nous explorons les bases pour commencer votre propre jardin en ville.',
                    'author' => 'Marie Jardiner',
                    'category' => 'jardinage',
                    'tags' => ['urbain', 'débutant', 'potager'],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'published',
                    'viewCount' => 125
                ],
                [
                    '_id' => new \MongoDB\BSON\ObjectId(),
                    'title' => 'Choisir les bonnes plantes pour son balcon',
                    'content' => 'Tous les légumes ne se prêtent pas à la culture en bac. Découvrez notre sélection de plantes parfaites pour les petits espaces.',
                    'author' => 'Pierre Planteur',
                    'category' => 'conseils',
                    'tags' => ['balcon', 'plantes', 'petit espace'],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'published',
                    'viewCount' => 89
                ],
                [
                    '_id' => new \MongoDB\BSON\ObjectId(),
                    'title' => 'Compostage en appartement : Guide complet',
                    'content' => 'Même en appartement, il est possible de composter ses déchets organiques. Voici comment procéder étape par étape.',
                    'author' => 'Sophie Écolo',
                    'category' => 'écologie',
                    'tags' => ['compost', 'appartement', 'zéro déchet'],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'updatedAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'draft',
                    'viewCount' => 0
                ]
            ];

            $result = $database->articles->insertMany($articles);
            $io->success(sprintf('Inserted %d articles', $result->getInsertedCount()));

            // Get article IDs for comments
            $articleIds = $result->getInsertedIds();

            // Insert comments
            $comments = [
                [
                    'articleId' => $articleIds[0],
                    'author' => 'Jean Commentateur',
                    'email' => 'jean@example.com',
                    'content' => 'Excellent article ! J\'ai commencé mon premier potager grâce à vos conseils.',
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'approved'
                ],
                [
                    'articleId' => $articleIds[0],
                    'author' => 'Anna Lectrice',
                    'email' => 'anna@example.com',
                    'content' => 'Très informatif, j\'aimerais voir plus d\'articles sur ce sujet.',
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'approved'
                ],
                [
                    'articleId' => $articleIds[1],
                    'author' => 'Paul Jardinier',
                    'email' => 'paul@example.com',
                    'content' => 'Quelles plantes recommandez-vous pour un balcon orienté nord ?',
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'status' => 'pending'
                ]
            ];

            $result = $database->comments->insertMany($comments);
            $io->success(sprintf('Inserted %d comments', $result->getInsertedCount()));

            // Insert users
            $users = [
                [
                    'username' => 'marie_jardiner',
                    'email' => 'marie@innergarden.com',
                    'role' => 'author',
                    'profile' => [
                        'firstName' => 'Marie',
                        'lastName' => 'Jardiner',
                        'bio' => 'Experte en jardinage urbain avec 10 ans d\'expérience',
                        'avatar' => 'avatars/marie.jpg'
                    ],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'lastLogin' => new \MongoDB\BSON\UTCDateTime()
                ],
                [
                    'username' => 'pierre_planteur',
                    'email' => 'pierre@innergarden.com',
                    'role' => 'author',
                    'profile' => [
                        'firstName' => 'Pierre',
                        'lastName' => 'Planteur',
                        'bio' => 'Spécialiste des petits espaces et balcons',
                        'avatar' => 'avatars/pierre.jpg'
                    ],
                    'createdAt' => new \MongoDB\BSON\UTCDateTime(),
                    'lastLogin' => new \MongoDB\BSON\UTCDateTime()
                ]
            ];

            $result = $database->users->insertMany($users);
            $io->success(sprintf('Inserted %d users', $result->getInsertedCount()));

            // Display summary
            $io->section('Database Summary');
            $collections = $database->listCollections();
            foreach ($collections as $collection) {
                $collectionName = $collection->getName();
                $count = $database->selectCollection($collectionName)->countDocuments();
                $io->text(sprintf('Collection "%s": %d documents', $collectionName, $count));
            }

            $io->success('MongoDB seed data inserted successfully!');

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Failed to insert seed data: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
