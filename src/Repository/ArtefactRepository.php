<?php

namespace App\Repository;

use App\Entity\Artefact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artefact>
 */
class ArtefactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artefact::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['createdAt' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type' => $type], ['createdAt' => 'DESC']);
    }

    public function findByUniverse(string $universe): array
    {
        return $this->findBy(['universe' => $universe], ['createdAt' => 'DESC']);
    }

    public function findByRarity(string $rarity): array
    {
        return $this->findBy(['rarity' => $rarity], ['createdAt' => 'DESC']);
    }
}
