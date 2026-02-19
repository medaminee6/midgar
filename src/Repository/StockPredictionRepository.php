<?php

namespace App\Repository;

use App\Entity\StockPrediction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StockPredictionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StockPrediction::class);
    }

    public function findLatestPredictions(int $limit = 100)
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.product', 'p')
            ->orderBy('sp.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findCriticalProducts(int $daysThreshold = 30)
    {
        return $this->createQueryBuilder('sp')
            ->join('sp.product', 'p')
            ->where('sp.days_until_stockout <= :threshold')
            ->setParameter('threshold', $daysThreshold)
            ->orderBy('sp.days_until_stockout', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
