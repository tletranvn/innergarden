<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /**
     * Trouver les messages non traités
     */
    public function findUnprocessed(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isProcessed = :processed')
            ->setParameter('processed', false)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compter les messages non traités
     */
    public function countUnprocessed(): int
    {
        return $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.isProcessed = :processed')
            ->setParameter('processed', false)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
