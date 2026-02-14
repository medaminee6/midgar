<?php

namespace App\Repository;

use App\Entity\Produit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Produit>
 */
class ProduitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Produit::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['date_ajout' => 'DESC']);
    }

    public function findByType(string $type): array
    {
        return $this->findBy(['type_produit' => $type], ['date_ajout' => 'DESC']);
    }

    /**
     * Search products by name with optional type filter and sorting
     */
    public function searchProduits(?string $search = null, ?string $type = null, string $sortBy = 'date'): array
    {
        $qb = $this->createQueryBuilder('p');

        // Filter by search term (product name)
        if ($search) {
            $qb->andWhere('p.nom_produit LIKE :search')
               ->setParameter('search', '%' . $search . '%');
        }

        // Filter by type
        if ($type) {
            $qb->andWhere('p.type_produit = :type')
               ->setParameter('type', $type);
        }

        // Apply sorting
        match($sortBy) {
            'price_asc' => $qb->orderBy('p.prix', 'ASC'),
            'price_desc' => $qb->orderBy('p.prix', 'DESC'),
            'name' => $qb->orderBy('p.nom_produit', 'ASC'),
            'stock' => $qb->orderBy('p.quantite_disponible', 'DESC'),
            default => $qb->orderBy('p.date_ajout', 'DESC'),
        };

        return $qb->getQuery()->getResult();
    }

    /**
     * Get all product types for filtering
     */
    public function getProductTypes(): array
    {
        return $this->createQueryBuilder('p')
            ->select('DISTINCT p.type_produit')
            ->orderBy('p.type_produit', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
