<?php
namespace App\Repository;

use App\Entity\Personnage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PersonnageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Personnage::class);
    }

    public function findFiltered(array $criteria = [])
    {
        $qb = $this->createQueryBuilder('p')
            ->join('p.universe', 'u')
            ->addSelect('u');

        // Handle universe filter (single or array)
        if (!empty($criteria['universe']) && is_array($criteria['universe'])) {
            $qb->andWhere('p.universe IN (:universe)')->setParameter('universe', $criteria['universe']);
        } elseif (!empty($criteria['universe'])) {
            $qb->andWhere('p.universe = :universe')->setParameter('universe', $criteria['universe']);
        }

        // Handle classRole filter (single or array)
        if (!empty($criteria['classRole']) && is_array($criteria['classRole'])) {
            $qb->andWhere('p.classRole IN (:classRole)')->setParameter('classRole', $criteria['classRole']);
        } elseif (!empty($criteria['classRole'])) {
            $qb->andWhere('p.classRole = :classRole')->setParameter('classRole', $criteria['classRole']);
        }

        if (!empty($criteria['search'])) {
            $qb->andWhere('p.name LIKE :search OR p.historyContext LIKE :search')
               ->setParameter('search', '%'.$criteria['search'].'%');
        }

        if (!empty($criteria['sort'])) {
            switch ($criteria['sort']) {
                case 'newest':
                    $qb->orderBy('p.createdAt', 'DESC');
                    break;
                case 'oldest':
                    $qb->orderBy('p.createdAt', 'ASC');
                    break;
                case 'name':
                    $qb->orderBy('p.name', 'ASC');
                    break;
                default:
                    $qb->orderBy('p.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('p.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
