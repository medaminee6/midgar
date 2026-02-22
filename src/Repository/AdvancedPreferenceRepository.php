<?php

namespace App\Repository;

use App\Entity\AdvancedPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AdvancedPreference>
 */
class AdvancedPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdvancedPreference::class);
    }

    /**
     * Find advanced preference for a specific user ID
     */
    public function findByUserId(string $userId): ?AdvancedPreference
    {
        return $this->createQueryBuilder('ap')
            ->andWhere('ap.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all advanced preferences ordered by creation date
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('ap')
            ->orderBy('ap.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
