<?php

namespace App\Repository;

use App\Entity\Enemy;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Enemy>
 *
 * @method Enemy|null find($id, $lockMode = null, $lockVersion = null)
 * @method Enemy|null findOneBy(array $criteria, array $orderBy = null)
 * @method Enemy[]    findAll()
 * @method Enemy[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EnemyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Enemy::class);
    }

    /**
     * Find all enemies for a specific universe
     */
    public function findByUniverse($universeId): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.universe = :universeId')
            ->setParameter('universeId', $universeId)
            ->orderBy('e.difficultyTier', 'ASC')
            ->addOrderBy('e.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find enemies by difficulty tier
     */
    public function findByDifficultyTier($tier): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.difficultyTier = :tier')
            ->setParameter('tier', $tier)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find random enemies for a universe and difficulty
     */
    public function findRandomForBattle($universeId, $tier = 1, $count = 1): array
    {
        $qb = $this->createQueryBuilder('e');

        if ($universeId) {
            $qb->where('e.universe = :universeId')
               ->setParameter('universeId', $universeId);
        }

        if ($tier) {
            $qb->andWhere('e.difficultyTier <= :tier')
               ->setParameter('tier', $tier);
        }

        return $qb->orderBy('RAND()')
            ->setMaxResults($count)
            ->getQuery()
            ->getResult();
    }
}
