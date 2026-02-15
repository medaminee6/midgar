<?php

namespace App\Entity;

use App\Repository\OeuvreRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\User;
use App\Entity\Universe;

#[ORM\Entity(repositoryClass: OeuvreRepository::class)]
#[ORM\Table(name: 'oeuvres')]
#[ORM\HasLifecycleCallbacks]
class Oeuvre
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le titre est requis")]
    #[Assert\Length(min: 2, minMessage: "Le titre doit contenir au moins 2 caractères")]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}\s\-\']*$/u', message: "Le titre ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)")]
    private ?string $title = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type est requis")]
    private ?string $type = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "La description est requise")]
    #[Assert\Length(min: 10, minMessage: "La description doit contenir au moins 10 caractères")]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}0-9\s\-,.!?\']*$/u', message: "La description doit commencer par une lettre")]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $datePublication = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}\s\-\']*$/u', message: "Le nom de l'auteur ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)")]
    private ?string $author = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;

    #[ORM\ManyToOne(targetEntity: Universe::class, inversedBy: 'oeuvres')]
    #[ORM\JoinColumn(name: 'universe_id', referencedColumnName: 'id', nullable: true)]
    private ?Universe $universe = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    // ===== GETTERS ET SETTERS =====
    public function getId(): ?int { return $this->id; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getType(): ?string { return $this->type; }
    public function setType(string $type): static { $this->type = $type; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(string $description): static { $this->description = $description; return $this; }

    public function getDatePublication(): ?\DateTimeImmutable { return $this->datePublication; }
    public function setDatePublication(?\DateTimeImmutable $datePublication): static { $this->datePublication = $datePublication; return $this; }

    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $imageUrl): static { $this->imageUrl = $imageUrl; return $this; }

    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): static { $this->author = $author; return $this; }

    public function getCreatedBy(): ?User { return $this->createdBy; }
    public function setCreatedBy(?User $createdBy): static { $this->createdBy = $createdBy; return $this; }

    public function getUniverse(): ?Universe { return $this->universe; }
    public function setUniverse(?Universe $universe): static { $this->universe = $universe; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): static { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static { $this->updatedAt = $updatedAt; return $this; }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
