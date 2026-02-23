<?php

namespace App\Entity;

use App\Repository\GameRunRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GameRunRepository::class)]
#[ORM\Table(name: 'game_run')]
class GameRun
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'gameRuns')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Personnage $personnage = null;

    #[ORM\Column]
    private ?int $health = null;

    #[ORM\Column]
    private ?int $mp = null;

    #[ORM\Column]
    private ?int $coins = null;

    #[ORM\Column]
    private ?int $kills = null;

    #[ORM\Column]
    private ?int $level = null;

    #[ORM\Column(type: Types::JSON)]
    private array $defeatedEnemies = [];

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $collectedCoins = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPersonnage(): ?Personnage
    {
        return $this->personnage;
    }

    public function setPersonnage(?Personnage $personnage): static
    {
        $this->personnage = $personnage;

        return $this;
    }

    public function getHealth(): ?int
    {
        return $this->health;
    }

    public function setHealth(int $health): static
    {
        $this->health = $health;

        return $this;
    }

    public function getMp(): ?int
    {
        return $this->mp;
    }

    public function setMp(int $mp): static
    {
        $this->mp = $mp;

        return $this;
    }

    public function getCoins(): ?int
    {
        return $this->coins;
    }

    public function setCoins(int $coins): static
    {
        $this->coins = $coins;

        return $this;
    }

    public function getKills(): ?int
    {
        return $this->kills;
    }

    public function setKills(int $kills): static
    {
        $this->kills = $kills;

        return $this;
    }

    public function getLevel(): ?int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getDefeatedEnemies(): array
    {
        return $this->defeatedEnemies;
    }

    public function setDefeatedEnemies(array $defeatedEnemies): static
    {
        $this->defeatedEnemies = $defeatedEnemies;

        return $this;
    }

    public function addDefeatedEnemy(int|string $enemyId): static
    {
        $id = is_string($enemyId) ? $enemyId : (string)$enemyId;
        if (!in_array($id, $this->defeatedEnemies)) {
            $this->defeatedEnemies[] = $id;
        }

        return $this;
    }

    public function getCollectedCoins(): ?array
    {
        return $this->collectedCoins ?? [];
    }

    public function setCollectedCoins(?array $collectedCoins): static
    {
        $this->collectedCoins = $collectedCoins ?? [];

        return $this;
    }

    public function addCollectedCoin(int $coinId): static
    {
        if (!in_array($coinId, $this->collectedCoins ?? [])) {
            $this->collectedCoins[] = $coinId;
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
