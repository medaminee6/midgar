<?php

namespace App\Entity;

use App\Repository\ArtefactRepository;
<<<<<<< HEAD
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
=======
use App\Entity\User;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
>>>>>>> validation-final-2

#[ORM\Entity(repositoryClass: ArtefactRepository::class)]
#[ORM\Table(name: 'artefacts')]
#[ORM\HasLifecycleCallbacks]
class Artefact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
<<<<<<< HEAD
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $universe = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $origins = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $powers = null;

    #[ORM\Column(length: 50)]
=======
    #[Assert\NotBlank(message: "Le nom est requis")]
    #[Assert\Length(min: 3, minMessage: "Le nom doit contenir au moins 3 caractères")]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}\s\'\-\x{2019}]*$/u', message: "Le nom ne doit contenir que des lettres (espaces, tirets et apostrophes autorisés)")]
    private ?string $name = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Le type est requis")]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "L'univers est requis")]
    #[Assert\Length(min: 2, minMessage: "L'univers doit contenir au moins 2 caractères")]
    private ?string $universe = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Les origines sont requises")]
    #[Assert\Length(min: 10, minMessage: "Les origines doivent contenir au moins 10 caractères")]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}0-9\s\-\.,!?\'\\x{2019}]*$/u', message: "Les origines doivent commencer par une lettre")]
    private ?string $origins = null;

    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank(message: "Les pouvoirs sont requis")]
    #[Assert\Length(min: 10, minMessage: "Les pouvoirs doivent contenir au moins 10 caractères")]
    #[Assert\Regex(pattern: '/^[\p{L}][\p{L}0-9\s\-\.,!?\'\\x{2019}]*$/u', message: "Les pouvoirs doivent commencer par une lettre")]
    private ?string $powers = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "La rareté est requise")]
>>>>>>> validation-final-2
    private ?string $rarity = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $imageUrl = null;

<<<<<<< HEAD
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $createdBy = null;
=======
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'created_by_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $createdBy = null;
>>>>>>> validation-final-2

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getUniverse(): ?string
    {
        return $this->universe;
    }

    public function setUniverse(string $universe): static
    {
        $this->universe = $universe;
        return $this;
    }

    public function getOrigins(): ?string
    {
        return $this->origins;
    }

    public function setOrigins(string $origins): static
    {
        $this->origins = $origins;
        return $this;
    }

    public function getPowers(): ?string
    {
        return $this->powers;
    }

    public function setPowers(string $powers): static
    {
        $this->powers = $powers;
        return $this;
    }

    public function getRarity(): ?string
    {
        return $this->rarity;
    }

    public function setRarity(string $rarity): static
    {
        $this->rarity = $rarity;
        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;
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

<<<<<<< HEAD
    public function getCreatedBy(): ?string
=======
    public function getCreatedBy(): ?User
>>>>>>> validation-final-2
    {
        return $this->createdBy;
    }

<<<<<<< HEAD
    public function setCreatedBy(?string $createdBy): static
=======
    public function setCreatedBy(?User $createdBy): static
>>>>>>> validation-final-2
    {
        $this->createdBy = $createdBy;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
