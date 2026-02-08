<?php

namespace App\Repository;

use App\Entity\Commande;
use App\Enum\CommandeEtat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Commande>
 */
class CommandeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Commande::class);
    }

    public function findAll(): array
    {
        return $this->findBy([], ['date_commande' => 'DESC']);
    }

    public function findByAcheteur(string $acheteur): array
    {
        return $this->findBy(['acheteur' => $acheteur], ['date_commande' => 'DESC']);
    }

    public function findByEtat(CommandeEtat $etat): array
    {
        return $this->findBy(['etat' => $etat], ['date_commande' => 'DESC']);
    }

    public function findByReference(string $reference): ?Commande
    {
        return $this->findOneBy(['reference_commande' => $reference]);
    }
}
