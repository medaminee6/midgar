<?php

namespace App\Repository;

use App\Entity\Produit;
use App\Entity\ShopAutomationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopAutomationEvent>
 */
class ShopAutomationEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopAutomationEvent::class);
    }

    public function hasRecentEvent(Produit $product, string $type, \DateTimeImmutable $cutoff): bool
    {
        $count = $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->andWhere('e.product = :product')
            ->andWhere('e.type = :type')
            ->andWhere('e.created_at >= :cutoff')
            ->setParameter('product', $product)
            ->setParameter('type', $type)
            ->setParameter('cutoff', $cutoff)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }
}
