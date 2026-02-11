<?php

namespace App\Repository;

use App\Entity\Oeuvre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Oeuvre>
 */
class OeuvreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Oeuvre::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['createdAt' => 'DESC']);
    }

    public function findByAuthor(string $author): array
    {
        return $this->findBy(['author' => $author], ['createdAt' => 'DESC']);
    }
}
