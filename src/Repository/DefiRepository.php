<?php

namespace App\Repository;

use App\Entity\Defi;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Defi>
 */
class DefiRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Defi::class);
    }

    /**
     * Trouve tous les défis ouverts
     */
    public function findOuverts()
    {
        return $this->createQueryBuilder('d')
            ->where('d.statut = :statut')
            ->setParameter('statut', 'OUVERT')
            ->orderBy('d.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les défis par thème
     */
    public function findByTheme(string $theme)
    {
        return $this->createQueryBuilder('d')
            ->where('d.theme = :theme')
            ->setParameter('theme', $theme)
            ->orderBy('d.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les défis actifs (non expirés)
     */
    public function findActifs()
    {
        return $this->createQueryBuilder('d')
            ->where('d.dateFin >= :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('d.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un défi par ID avec ses participations
     */
    public function findWithParticipations(int $id)
    {
        return $this->createQueryBuilder('d')
            ->leftJoin('d.participations', 'p')
            ->addSelect('p')
            ->where('d.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Compte les défis par statut
     */
    public function countByStatut(string $statut): int
    {
        return $this->createQueryBuilder('d')
            ->select('COUNT(d.id)')
            ->where('d.statut = :statut')
            ->setParameter('statut', $statut)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Recherche les défis par titre ou thème
     */
    public function searchDefis(?string $search = null, ?string $sortBy = 'recent')
    {
        $qb = $this->createQueryBuilder('d');
        
        if ($search) {
            $qb->where('d.titre LIKE :search OR d.theme LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }
        
        // Tri
        match($sortBy) {
            'titre' => $qb->orderBy('d.titre', 'ASC'),
            'theme' => $qb->orderBy('d.theme', 'ASC'),
            'ancien' => $qb->orderBy('d.dateDebut', 'ASC'),
            default => $qb->orderBy('d.dateDebut', 'DESC'), // 'recent'
        };
        
        return $qb->getQuery()->getResult();
    }
}
