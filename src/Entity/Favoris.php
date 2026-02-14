<?php

namespace App\Entity;

use App\Repository\FavorisRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FavorisRepository::class)]
#[ORM\Table(name: 'favoris')]
#[ORM\HasLifecycleCallbacks]
class Favoris
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $userId = null;

    #[ORM\Column(nullable: true)]
    private ?int $oeuvreId = null;

    #[ORM\Column(nullable: true)]
    private ?int $artefactId = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): self
    {
        $this->userId = $userId;
        return $this;
    }

    public function getOeuvreId(): ?int
    {
        return $this->oeuvreId;
    }

    public function setOeuvreId(?int $oeuvreId): self
    {
        $this->oeuvreId = $oeuvreId;
        return $this;
    }

    public function getArtefactId(): ?int
    {
        return $this->artefactId;
    }

    public function setArtefactId(?int $artefactId): self
    {
        $this->artefactId = $artefactId;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTime();
    }
}
