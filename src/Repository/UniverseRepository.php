<?php
namespace App\Repository;

use App\Entity\Universe;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UniverseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Universe::class);
    }

    /**
     * Find universes with optional filters: genre, search, sort
     * @param array $criteria
     */
    public function findFiltered(array $criteria = [])
    {
        $qb = $this->createQueryBuilder('u');

        if (!empty($criteria['genre'])) {
            $qb->andWhere('u.genre = :genre')->setParameter('genre', $criteria['genre']);
        }

        if (!empty($criteria['search'])) {
            $qb->andWhere('u.name LIKE :search OR u.shortDescription LIKE :search')
               ->setParameter('search', '%'.$criteria['search'].'%');
        }

        if (!empty($criteria['sort'])) {
            switch ($criteria['sort']) {
                case 'newest':
                    $qb->orderBy('u.createdAt', 'DESC');
                    break;
                case 'oldest':
                    $qb->orderBy('u.createdAt', 'ASC');
                    break;
                case 'name':
                    $qb->orderBy('u.name', 'ASC');
                    break;
                default:
                    $qb->orderBy('u.createdAt', 'DESC');
            }
        } else {
            $qb->orderBy('u.createdAt', 'DESC');
        }

        return $qb->getQuery()->getResult();
    }
}
