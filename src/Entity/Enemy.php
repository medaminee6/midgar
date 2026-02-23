<?php
namespace App\Entity;

use App\Repository\EnemyRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EnemyRepository::class)]
#[ORM\Table(name: 'enemy')]
#[ORM\HasLifecycleCallbacks]
class Enemy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'ennemi est obligatoire.")]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private ?string $enemyType = null; // 'goblin', 'skeleton', 'dragon', etc.

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    // Combat stats
    #[ORM\Column(name: 'strength', type: 'integer')]
    #[Assert\Range(min: 1, max: 100)]
    private ?int $strength = 10;

    #[ORM\Column(name: 'agility', type: 'integer')]
    #[Assert\Range(min: 1, max: 100)]
    private ?int $agility = 10;

    #[ORM\Column(name: 'magic', type: 'integer')]
    #[Assert\Range(min: 0, max: 100)]
    private ?int $magic = 0;

    #[ORM\Column(name: 'defense', type: 'integer')]
    #[Assert\Range(min: 1, max: 100)]
    private ?int $defense = 5;

    #[ORM\Column(name: 'max_hp', type: 'integer')]
    #[Assert\Range(min: 1, max: 1000)]
    private ?int $maxHp = 30;

    // Difficulty level (affects stat scaling)
    #[ORM\Column(name: 'difficulty_tier', type: 'integer')]
    private ?int $difficultyTier = 1; // 1 = weak, 2 = normal, 3 = strong, 4 = boss

    // Visual and behavioral properties
    #[ORM\Column(name: 'color_hex', type: 'string', length: 7, nullable: true)]
    private ?string $colorHex = '#888888'; // Default gray

    #[ORM\Column(name: 'portrait_image', type: 'blob', nullable: true)]
    private $portraitImage = null;

    #[ORM\Column(name: 'behavior_type', type: 'string', length: 50)]
    private ?string $behaviorType = 'patrol'; // 'patrol', 'aggressive', 'ranged', 'healer'

    #[ORM\Column(name: 'loot_xp', type: 'integer')]
    private ?int $lootXp = 10;

    #[ORM\Column(name: 'loot_gold', type: 'integer')]
    private ?int $lootGold = 0;

    #[ORM\ManyToOne(targetEntity: Universe::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Universe $universe = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PrePersist]
    public function onPrePersist(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function onPreUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getEnemyType(): ?string
    {
        return $this->enemyType;
    }

    public function setEnemyType(string $enemyType): self
    {
        $this->enemyType = $enemyType;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getStrength(): ?int
    {
        return $this->strength;
    }

    public function setStrength(int $strength): self
    {
        $this->strength = $strength;
        return $this;
    }

    public function getAgility(): ?int
    {
        return $this->agility;
    }

    public function setAgility(int $agility): self
    {
        $this->agility = $agility;
        return $this;
    }

    public function getMagic(): ?int
    {
        return $this->magic;
    }

    public function setMagic(int $magic): self
    {
        $this->magic = $magic;
        return $this;
    }

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(int $defense): self
    {
        $this->defense = $defense;
        return $this;
    }

    public function getMaxHp(): ?int
    {
        return $this->maxHp;
    }

    public function setMaxHp(int $maxHp): self
    {
        $this->maxHp = $maxHp;
        return $this;
    }

    public function getDifficultyTier(): ?int
    {
        return $this->difficultyTier;
    }

    public function setDifficultyTier(int $difficultyTier): self
    {
        $this->difficultyTier = $difficultyTier;
        return $this;
    }

    public function getColorHex(): ?string
    {
        return $this->colorHex;
    }

    public function setColorHex(?string $colorHex): self
    {
        $this->colorHex = $colorHex;
        return $this;
    }

    public function getPortraitImage()
    {
        if ($this->portraitImage === null) {
            return null;
        }
        
        // If it's a string and already contains data URI, return it
        if (is_string($this->portraitImage) && strpos($this->portraitImage, 'data:') === 0) {
            return $this->portraitImage;
        }
        
        // Try to convert BLOB to base64
        try {
            if (is_resource($this->portraitImage)) {
                $content = stream_get_contents($this->portraitImage);
                if ($content !== false && strlen($content) > 0) {
                    return 'data:image/png;base64,' . base64_encode($content);
                }
            } elseif (is_object($this->portraitImage) && method_exists($this->portraitImage, 'getContents')) {
                // It's a stream
                $content = $this->portraitImage->getContents();
                if ($content !== false && strlen($content) > 0) {
                    return 'data:image/png;base64,' . base64_encode($content);
                }
            } elseif (is_string($this->portraitImage) && strlen($this->portraitImage) > 100) {
                // It's already raw binary data
                return 'data:image/png;base64,' . base64_encode($this->portraitImage);
            }
        } catch (\Exception $e) {
            // Log error but return null
            return null;
        }
        
        return null;
    }

    public function setPortraitImage($portraitImage): self
    {
        $this->portraitImage = $portraitImage;
        return $this;
    }

    public function getBehaviorType(): ?string
    {
        return $this->behaviorType;
    }

    public function setBehaviorType(string $behaviorType): self
    {
        $this->behaviorType = $behaviorType;
        return $this;
    }

    public function getLootXp(): ?int
    {
        return $this->lootXp;
    }

    public function setLootXp(int $lootXp): self
    {
        $this->lootXp = $lootXp;
        return $this;
    }

    public function getLootGold(): ?int
    {
        return $this->lootGold;
    }

    public function setLootGold(int $lootGold): self
    {
        $this->lootGold = $lootGold;
        return $this;
    }

    public function getUniverse(): ?Universe
    {
        return $this->universe;
    }

    public function setUniverse(?Universe $universe): self
    {
        $this->universe = $universe;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
