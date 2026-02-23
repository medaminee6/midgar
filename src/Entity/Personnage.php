<?php
namespace App\Entity;

use App\Repository\PersonnageRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PersonnageRepository::class)]
#[ORM\Table(name: 'personnage')]
#[ORM\HasLifecycleCallbacks]
class Personnage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank(message: "Le nom est obligatoire.")]
    #[Assert\Length(min: 2, max: 255, minMessage: "Le nom doit contenir au moins 2 caractères.")]
    private ?string $name = null;

    #[ORM\Column(name: 'class_role', type: 'string', length: 100)]
    #[Assert\NotBlank(message: "La classe est obligatoire.")]
    private ?string $classRole = null;

    #[ORM\Column(name: 'history_context', type: 'text')]
    #[Assert\NotBlank(message: "L'histoire est obligatoire.")]
    #[Assert\Length(min: 30, minMessage: "L'histoire doit contenir au moins 30 caractères.")]
    private ?string $historyContext = null;

    #[ORM\Column(name: 'abilities_powers', type: 'text', nullable: true)]
    private ?string $abilitiesPowers = null;

    // Stats attributes
    #[ORM\Column(name: 'strength', type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "La force doit être comprise entre {{ min }} et {{ max }}.")]
    private ?int $strength = null;

    #[ORM\Column(name: 'agility', type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "L'agilité doit être comprise entre {{ min }} et {{ max }}.")]
    private ?int $agility = null;

    #[ORM\Column(name: 'magic', type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "La magie doit être comprise entre {{ min }} et {{ max }}.")]
    private ?int $magic = null;

    #[ORM\Column(name: 'defense', type: 'integer', nullable: true)]
    #[Assert\Range(min: 0, max: 100, notInRangeMessage: "La défense doit être comprise entre {{ min }} et {{ max }}.")]
    private ?int $defense = null;

    // portrait stored as LONGBLOB
    #[ORM\Column(name: 'portrait_image', type: 'blob', nullable: true)]
    private $portraitImage = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 255)]
    private string $tags = '';

    #[ORM\ManyToOne(targetEntity: Universe::class, inversedBy: 'personnages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull]
    private ?Universe $universe = null;

    #[ORM\OneToMany(mappedBy: 'personnage', targetEntity: GameRun::class)]
    private Collection $gameRuns;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->gameRuns = new ArrayCollection();
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

    public function getClassRole(): ?string
    {
        return $this->classRole;
    }

    public function setClassRole(string $classRole): self
    {
        $this->classRole = $classRole;

        return $this;
    }

    public function getHistoryContext(): ?string
    {
        return $this->historyContext;
    }

    public function setHistoryContext(string $historyContext): self
    {
        $this->historyContext = $historyContext;

        return $this;
    }

    public function getAbilitiesPowers(): ?string
    {
        return $this->abilitiesPowers;
    }

    public function setAbilitiesPowers(?string $abilitiesPowers): self
    {
        $this->abilitiesPowers = $abilitiesPowers;

        return $this;
    }

    public function getStrength(): ?int
    {
        return $this->strength;
    }

    public function setStrength(?int $strength): self
    {
        $this->strength = $strength;

        return $this;
    }

    public function getAgility(): ?int
    {
        return $this->agility;
    }

    public function setAgility(?int $agility): self
    {
        $this->agility = $agility;

        return $this;
    }

    public function getMagic(): ?int
    {
        return $this->magic;
    }

    public function setMagic(?int $magic): self
    {
        $this->magic = $magic;

        return $this;
    }

    public function getDefense(): ?int
    {
        return $this->defense;
    }

    public function setDefense(?int $defense): self
    {
        $this->defense = $defense;

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
                @rewind($this->portraitImage);
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

    public function getTags(): string
    {
        return $this->tags;
    }

    public function setTags(string $tags): self
    {
        $this->tags = $tags;
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

    public function getUniverse(): ?Universe
    {
        return $this->universe;
    }

    public function setUniverse(?Universe $universe): self
    {
        $this->universe = $universe;

        return $this;
    }

    /**
     * @return Collection<int, GameRun>
     */
    public function getGameRuns(): Collection
    {
        return $this->gameRuns;
    }

    public function addGameRun(GameRun $gameRun): self
    {
        if (!$this->gameRuns->contains($gameRun)) {
            $this->gameRuns->add($gameRun);
            $gameRun->setPersonnage($this);
        }

        return $this;
    }

    public function removeGameRun(GameRun $gameRun): self
    {
        if ($this->gameRuns->removeElement($gameRun)) {
            // set the owning side to null (unless already changed)
            if ($gameRun->getPersonnage() === $this) {
                $gameRun->setPersonnage(null);
            }
        }

        return $this;
    }

    public function getPortraitBase64(): ?string
    {
        if ($this->portraitImage === null) {
            return null;
        }
        
        $data = null;
        
        if (is_resource($this->portraitImage)) {
            // Read the stream content
            $data = stream_get_contents($this->portraitImage);
            // Try to rewind for future reads
            rewind($this->portraitImage);
        } elseif (is_string($this->portraitImage)) {
            $data = $this->portraitImage;
        } else {
            try {
                // Try casting to string for stream wrapper objects
                $data = (string) $this->portraitImage;
            } catch (\Exception $e) {
                return null;
            }
        }
        
        if (empty($data) || strlen($data) < 10) {
            return null;
        }
        
        return 'data:image/jpeg;base64,' . base64_encode($data);
    }
}
