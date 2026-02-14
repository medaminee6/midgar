<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Trouver un utilisateur actif par email
     */
    public function findActiveByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :email')
            ->andWhere('u.isBlocked = false')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Récupérer tous les admins
     */
    public function findAdmins(): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.role = :role')
            ->setParameter('role', 'admin')
            ->getQuery()
            ->getResult();
    }
    public function searchPublicUsers(string $q): array
{
    return $this->createQueryBuilder('u')
        ->andWhere('u.username LIKE :q OR u.username LIKE :q')
        ->setParameter('q', '%' . $q . '%')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
        
}
public function searchUsersApi(string $query): array
{
    $users = $this->createQueryBuilder('u')
        ->where('u.username LIKE :query')
        ->orWhere('u.prenom LIKE :query')
        ->orWhere('u.nom LIKE :query')
        ->andWhere('u.isBlocked = :blocked')
        ->setParameter('query', '%' . $query . '%')
        ->setParameter('blocked', false)
        ->orderBy('u.username', 'ASC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();
    
    $data = [];
    foreach ($users as $user) {
        $data[] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'prenom' => $user->getPrenom(),
            'nom' => $user->getNom(),
            'avatar' => $user->getAvatar(),
            'createdAt' => $user->getCreatedAt()->format('d/m/Y')
        ];
    }
    
    return $data;
}

}
