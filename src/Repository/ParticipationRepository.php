<?php

namespace App\Repository;

use App\Entity\Participation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * Trouve toutes les participations d'un utilisateur
     */
    public function findByUserId(int $userId)
    {
        return $this->createQueryBuilder('p')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les participations pour un défi
     */
    public function findByDefi(int $defiId)
    {
        return $this->createQueryBuilder('p')
            ->where('p.defi = :defiId')
            ->setParameter('defiId', $defiId)
            ->orderBy('p.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les participations par statut
     */
    public function findByStatut(string $statut)
    {
        return $this->createQueryBuilder('p')
            ->where('p.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('p.dateSoumission', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve une participation par user et defi
     */
    public function findByUserAndDefi(int $userId, int $defiId)
    {
        return $this->createQueryBuilder('p')
            ->where('p.userId = :userId')
            ->andWhere('p.defi = :defiId')
            ->setParameter('userId', $userId)
            ->setParameter('defiId', $defiId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les participations acceptées pour un défi
     */
    public function countAcceptedByDefi(int $defiId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.defi = :defiId')
            ->andWhere('p.statut = :statut')
            ->setParameter('defiId', $defiId)
            ->setParameter('statut', 'ACCEPTEE')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Compte les participations d'un utilisateur
     */
    public function countByUser(int $userId): int
    {
        return $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.userId = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche les participations par userId optionnel
     */
    public function searchParticipations(?int $userId = null)
    {
        $qb = $this->createQueryBuilder('p');
        
        if ($userId) {
            $qb->where('p.userId = :userId')
               ->setParameter('userId', $userId);
        }
        
        return $qb->orderBy('p.dateSoumission', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}
