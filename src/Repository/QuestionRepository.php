<?php

namespace App\Repository;

use App\Entity\Question;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<Question>
 */
class QuestionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Question::class);
    }

    /**
     * Get all questions with optional search and sorting
     * 
     * @param string|null $search Search term for question text
     * @param string $sort Field to sort by (createdAt or question)
     * @param string $direction Sort direction (asc or desc)
     */
    public function searchAndSort(?string $search = null, string $sort = 'createdAt', string $direction = 'desc'): array
    {
        $qb = $this->createQueryBuilder('q');

        if ($search) {
            $qb->andWhere('q.question LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        // Default sort is createdAt
        $sortField = match($sort) {
            'question' => 'q.question',
            'createdAt' => 'q.createdAt',
            default => 'q.createdAt'
        };

        $dir = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $dir);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find all questions ordered by created date
     */
    public function findAllOrderedByDate(): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find all questions ordered alphabetically
     */
    public function findAllOrderedAlphabetically(): array
    {
        return $this->createQueryBuilder('q')
            ->orderBy('q.question', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
