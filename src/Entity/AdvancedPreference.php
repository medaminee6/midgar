<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'advanced_preferences')]
class AdvancedPreference
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'La description libre ne peut pas être vide.')]
    private string $freeDescription = '';

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\Choice(choices: ['Epic Fantasy', 'Dark Fantasy', 'Heroic Fantasy', 'Medieval Fantasy', 'Sci-Fi Fantasy'], message: 'Veuillez sélectionner un genre valide.')]
    private string $favoriteGenre = '';

    #[ORM\Column(type: 'integer')]
    #[Assert\Range(min: 1, max: 10, notInRangeMessage: 'L\'affinité doit être entre 1 et 10.')]
    private int $affinityLevel = 1;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Les thèmes favoris ne peuvent pas être vides.')]
    private string $favoriteThemes = '';

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Les tags personnalisés ne peuvent pas être vides.')]
    private string $customTags = '';

    #[ORM\Column(type: 'string', length: 100)]
    private string $userId = '';

    #[ORM\Column(type: 'datetime')]
    private \DateTime $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $updatedAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFreeDescription(): string
    {
        return $this->freeDescription;
    }

    public function setFreeDescription(string $freeDescription): self
    {
        $this->freeDescription = $freeDescription;
        return $this;
    }

    public function getFavoriteGenre(): string
    {
        return $this->favoriteGenre;
    }

    public function setFavoriteGenre(string $favoriteGenre): self
    {
        $this->favoriteGenre = $favoriteGenre;
        return $this;
    }

    public function getAffinityLevel(): int
    {
        return $this->affinityLevel;
    }

    public function setAffinityLevel(int $affinityLevel): self
    {
        $this->affinityLevel = $affinityLevel;
        return $this;
    }

    public function getFavoriteThemes(): string
    {
        return $this->favoriteThemes;
    }

    public function setFavoriteThemes(string $favoriteThemes): self
    {
        $this->favoriteThemes = $favoriteThemes;
        return $this;
    }

    public function getCustomTags(): string
    {
        return $this->customTags;
    }

    public function setCustomTags(string $customTags): self
    {
        $this->customTags = $customTags;
        return $this;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }

    public function setUserId(string $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): \DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
