<?php

namespace App\Repository;

use App\Entity\UserPreference;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserPreference>
 */
class UserPreferenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserPreference::class);
    }

    public function save(UserPreference $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(UserPreference $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find preferences for a user
     */
    public function findByUser($userId)
    {
        return $this->findBy(['user' => $userId], ['createdAt' => 'DESC']);
    }

    /**
     * @return string[] Distinct email addresses
     */
    public function findRecipientEmailsByTag(string $tag): array
    {
        $normalizedTag = trim($tag);
        if ($normalizedTag === '') {
            return [];
        }

        $rows = $this->createQueryBuilder('up')
            ->select('DISTINCT u.email AS email')
            ->innerJoin('up.user', 'u')
            ->andWhere('up.tags LIKE :pattern')
            ->andWhere('u.email IS NOT NULL')
            ->andWhere('u.email <> :empty')
            ->setParameter('pattern', '%' . $normalizedTag . '%')
            ->setParameter('empty', '')
            ->getQuery()
            ->getArrayResult();

        $emails = [];
        foreach ($rows as $row) {
            if (!isset($row['email'])) {
                continue;
            }
            $email = trim((string) $row['email']);
            if ($email === '') {
                continue;
            }
            $emails[] = $email;
        }

        return array_values(array_unique($emails));
    }
}
