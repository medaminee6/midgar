<?php

namespace App\Entity;

use App\Enum\StatutParticipation;
use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
#[Vich\Uploadable]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La description ne doit pas être vide')]
    #[Assert\Length(min: 5, minMessage: 'La description doit contenir au moins 5 caractères')]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotNull(message: 'La date de soumission ne doit pas être vide')]
    private ?\DateTime $dateSoumission = null;

    #[ORM\Column(enumType: StatutParticipation::class)]
    private ?StatutParticipation $statut = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'L\'utilisateur ne doit pas être vide')]
    #[Assert\Positive(message: 'L\'ID utilisateur doit être positif')]
    private ?int $userId = null;

    #[ORM\Column(nullable: true)]
    #[Assert\Positive(message: 'L\'ID artwork doit être positif')]
    private ?int $artworkId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFileName = null;

    #[Vich\UploadableField(mapping: 'participation_image', fileNameProperty: 'imageFileName')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Veuillez téléverser une image (JPEG, PNG, GIF ou WebP).'
    )]
    private ?File $imageFile = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Le défi ne doit pas être vide')]
    private ?Defi $defi = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateSoumission(): ?\DateTime
    {
        return $this->dateSoumission;
    }

    public function setDateSoumission(\DateTime $dateSoumission): static
    {
        $this->dateSoumission = $dateSoumission;

        return $this;
    }

    public function getStatut(): ?StatutParticipation
    {
        return $this->statut;
    }

    public function setStatut(StatutParticipation $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getUserId(): ?int
    {
        return $this->userId;
    }

    public function setUserId(int $userId): static
    {
        $this->userId = $userId;

        return $this;
    }

    public function getArtworkId(): ?int
    {
        return $this->artworkId;
    }

    public function setArtworkId(?int $artworkId): static
    {
        $this->artworkId = $artworkId;

        return $this;
    }

    public function getImageFileName(): ?string
    {
        return $this->imageFileName;
    }

    public function setImageFileName(?string $imageFileName): static
    {
        $this->imageFileName = $imageFileName;

        return $this;
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImageFile(?File $imageFile): static
    {
        $this->imageFile = $imageFile;
        if ($imageFile instanceof UploadedFile && $imageFile->isValid()) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getDefi(): ?Defi
    {
        return $this->defi;
    }

    public function setDefi(?Defi $defi): static
    {
        $this->defi = $defi;

        return $this;
    }
}
