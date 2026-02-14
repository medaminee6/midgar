<?php

namespace App\Repository;

use App\Entity\Reponse;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reponse>
 */
class ReponseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reponse::class);
    }

    /**
     * Search reponses by option or tag
     * 
     * @param string|null $search Search term
     * @param string $sort Field to sort by
     * @param string $direction Sort direction (asc or desc)
     */
    public function searchAndSort(?string $search = null, string $sort = 'id', string $direction = 'asc'): array
    {
        $qb = $this->createQueryBuilder('r');

        if ($search) {
            $qb->andWhere('r.option LIKE :search OR r.tag LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $sortField = match($sort) {
            'option' => 'r.option',
            'tag' => 'r.tag',
            'id' => 'r.id',
            default => 'r.id'
        };

        $dir = strtoupper($direction) === 'DESC' ? 'DESC' : 'ASC';
        $qb->orderBy($sortField, $dir);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all reponses for a specific question
     */
    public function findByQuestion(int $questionId): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.question = :questionId')
            ->setParameter('questionId', $questionId)
            ->orderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
