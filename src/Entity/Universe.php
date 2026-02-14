<?php

namespace App\Entity;

use App\Repository\UniverseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UniverseRepository::class)]
#[ORM\Table(name: 'universe')]
#[ORM\UniqueConstraint(name: 'uniq_universe_name', columns: ['name'])]
#[ORM\HasLifecycleCallbacks]
class Universe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 3, max: 255)]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank]
    private ?string $genre = null;

    #[ORM\Column(name: 'short_description', type: 'string', length: 500)]
    #[Assert\NotBlank]
    #[Assert\Length(min: 10, max: 500)]
    private ?string $shortDescription = null;

    #[ORM\Column(name: 'story_context', type: 'text')]
    #[Assert\NotBlank]
    #[Assert\Length(min: 50)]
    private ?string $storyContext = null;

    #[ORM\Column(name: 'themes', type: 'json', nullable: true)]
    private array $themes = [];

    // store raw binary in LONGBLOB
    #[ORM\Column(name: 'banner_image', type: 'blob', nullable: true)]
    private $bannerImage = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\OneToMany(mappedBy: 'universe', targetEntity: Personnage::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $personnages;

    #[ORM\OneToMany(mappedBy: 'universe', targetEntity: Oeuvre::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $oeuvres;

    public function __construct()
    {
        $this->themes = [];
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->personnages = new ArrayCollection();
        $this->oeuvres = new ArrayCollection();
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

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(string $genre): self
    {
        $this->genre = $genre;

        return $this;
    }

    public function getShortDescription(): ?string
    {
        return $this->shortDescription;
    }

    public function setShortDescription(string $shortDescription): self
    {
        $this->shortDescription = $shortDescription;

        return $this;
    }

    public function getStoryContext(): ?string
    {
        return $this->storyContext;
    }

    public function setStoryContext(string $storyContext): self
    {
        $this->storyContext = $storyContext;

        return $this;
    }

    public function getThemes(): array
    {
        return $this->themes;
    }

    public function setThemes(array $themes): self
    {
        $this->themes = $themes;

        return $this;
    }

    public function getBannerImage()
    {
        return $this->bannerImage;
    }

    public function setBannerImage($bannerImage): self
    {
        $this->bannerImage = $bannerImage;

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

    /**
     * @return Collection<int, Personnage>
     */
    public function getPersonnages(): Collection
    {
        return $this->personnages;
    }

    public function addPersonnage(Personnage $personnage): self
    {
        if (!$this->personnages->contains($personnage)) {
            $this->personnages->add($personnage);
            $personnage->setUniverse($this);
        }

        return $this;
    }

    public function removePersonnage(Personnage $personnage): self
    {
        if ($this->personnages->removeElement($personnage)) {
            // set the owning side to null (unless already changed)
            if ($personnage->getUniverse() === $this) {
                $personnage->setUniverse(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Oeuvre>
     */
    public function getOeuvres(): Collection
    {
        return $this->oeuvres;
    }

    public function addOeuvre(Oeuvre $oeuvre): self
    {
        if (!$this->oeuvres->contains($oeuvre)) {
            $this->oeuvres->add($oeuvre);
            $oeuvre->setUniverse($this);
        }

        return $this;
    }

    public function removeOeuvre(Oeuvre $oeuvre): self
    {
        if ($this->oeuvres->removeElement($oeuvre)) {
            // set the owning side to null (unless already changed)
            if ($oeuvre->getUniverse() === $this) {
                $oeuvre->setUniverse(null);
            }
        }

        return $this;
    }

    public function getBannerBase64(): ?string
    {
        if ($this->bannerImage === null) {
            return null;
        }
        
        $data = null;
        
        if (is_resource($this->bannerImage)) {
            // Read the stream content
            $data = stream_get_contents($this->bannerImage);
            // Try to rewind for future reads
            rewind($this->bannerImage);
        } elseif (is_string($this->bannerImage)) {
            $data = $this->bannerImage;
        } else {
            try {
                // Try casting to string for stream wrapper objects
                $data = (string) $this->bannerImage;
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