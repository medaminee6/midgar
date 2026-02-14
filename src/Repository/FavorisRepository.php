<?php

namespace App\Repository;

use App\Entity\Favoris;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Favoris>
 */
class FavorisRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Favoris::class);
    }

    public function findByUserAndOeuvre(int $userId, int $oeuvreId): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :userId')
            ->andWhere('f.oeuvreId = :oeuvreId')
            ->setParameter('userId', $userId)
            ->setParameter('oeuvreId', $oeuvreId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findByUserAndArtefact(int $userId, int $artefactId): ?Favoris
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :userId')
            ->andWhere('f.artefactId = :artefactId')
            ->setParameter('userId', $userId)
            ->setParameter('artefactId', $artefactId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findFavoriOeuvresByUser(int $userId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :userId')
            ->andWhere('f.oeuvreId IS NOT NULL')
            ->setParameter('userId', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findFavoriArtefactsByUser(int $userId): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.userId = :userId')
            ->andWhere('f.artefactId IS NOT NULL')
            ->setParameter('userId', $userId)
            ->orderBy('f.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countOeuvreLikes(int $oeuvreId): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.oeuvreId = :oeuvreId')
            ->setParameter('oeuvreId', $oeuvreId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countArtefactLikes(int $artefactId): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->andWhere('f.artefactId = :artefactId')
            ->setParameter('artefactId', $artefactId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countAll(): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
