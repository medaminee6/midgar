<?php

namespace App\Entity;

use App\Enum\StatutDefiEnum;
use App\Enum\DifficulteEnum;
use App\Repository\DefiRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: DefiRepository::class)]
#[Vich\Uploadable]
class Defi
{
    public function __construct()
    {
        $this->participations = new ArrayCollection();
    }

    #[ORM\OneToMany(mappedBy: 'defi', targetEntity: Participation::class, cascade: ['persist','remove'])]
    private Collection $participations;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le titre ne doit pas être vide')]
    #[Assert\Length(min: 3, max: 255, minMessage: 'Le titre doit contenir au moins 3 caractères', maxMessage: 'Le titre ne peut pas dépasser 255 caractères')]
    private ?string $titre = null;

    #[ORM\Column(length: 850)]
    #[Assert\NotBlank(message: 'La description ne doit pas être vide')]
    #[Assert\Length(min: 10, max: 850, minMessage: 'La description doit contenir au moins 10 caractères', maxMessage: 'La description ne peut pas dépasser 850 caractères')]
    private ?string $description = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le thème ne doit pas être vide')]
    private ?string $theme = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageCover = null;

    #[Vich\UploadableField(mapping: 'defi_image', fileNameProperty: 'imageCover')]
    #[Assert\File(
        maxSize: '2M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Veuillez téléverser une image (JPEG, PNG, GIF ou WebP).'
    )]
    private ?File $imageCoverFile = null;

    // Image de référence pour aider les participants à peindre
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageReference = null;

    #[Vich\UploadableField(mapping: 'defi_reference', fileNameProperty: 'imageReference')]
    #[Assert\File(
        maxSize: '5M',
        mimeTypes: ['image/jpeg', 'image/png', 'image/gif', 'image/webp'],
        mimeTypesMessage: 'Veuillez téléverser une image de référence (JPEG, PNG, GIF ou WebP).'
    )]
    private ?File $imageReferenceFile = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTime $dateFin = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTime $dateLimite = null;

    #[ORM\Column(enumType: StatutDefiEnum::class)]
    private StatutDefiEnum $statut = StatutDefiEnum::OUVERT;

    #[ORM\Column(enumType: DifficulteEnum::class, nullable: true)]
    private ?DifficulteEnum $difficulte = null;

    #[ORM\Column]
    private ?int $createurId = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTime $updatedAt = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
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

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    public function getImageCover(): ?string
    {
        return $this->imageCover;
    }

    public function setImageCover(?string $imageCover): static
    {
        $this->imageCover = $imageCover;

        return $this;
    }

    public function getImageCoverFile(): ?File
    {
        return $this->imageCoverFile;
    }

    public function setImageCoverFile(?File $imageCoverFile): static
    {
        // Ne pas stocker un fichier "vide" (aucune sélection) pour éviter l'échec de validation
        if ($imageCoverFile instanceof UploadedFile && !$imageCoverFile->isValid()) {
            $imageCoverFile = null;
        }
        $this->imageCoverFile = $imageCoverFile;
        if (null !== $imageCoverFile) {
            $this->updatedAt = new \DateTime();
        }

        return $this;
    }

    // Méthodes pour imageReference (image de référence pour peindre)
    public function getImageReference(): ?string
    {
        return $this->imageReference;
    }

    public function setImageReference(?string $imageReference): static
    {
        $this->imageReference = $imageReference;

        return $this;
    }

    public function getImageReferenceFile(): ?File
    {
        return $this->imageReferenceFile;
    }

    public function setImageReferenceFile(?File $imageReferenceFile): static
    {
        if ($imageReferenceFile instanceof UploadedFile && !$imageReferenceFile->isValid()) {
            $imageReferenceFile = null;
        }
        $this->imageReferenceFile = $imageReferenceFile;
        if (null !== $imageReferenceFile) {
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

    public function getDateDebut(): ?\DateTime
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTime $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTime
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTime $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getDateLimite(): ?\DateTime
    {
        return $this->dateLimite;
    }

    public function setDateLimite(?\DateTime $dateLimite): static
    {
        $this->dateLimite = $dateLimite;

        return $this;
    }

    public function getStatut(): StatutDefiEnum
    {
        return $this->statut;
    }

    public function setStatut(StatutDefiEnum $statut): static
    {
        $this->statut = $statut;

        return $this;
    }

    public function getDifficulte(): ?DifficulteEnum
    {
        return $this->difficulte;
    }

    public function setDifficulte(?DifficulteEnum $difficulte): static
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    public function getCreateurId(): ?int
    {
        return $this->createurId;
    }

    public function setCreateurId(int $createurId): static
    {
        $this->createurId = $createurId;

        return $this;
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setDefi($this);
        }

        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            if ($participation->getDefi() === $this) {
                $participation->setDefi(null);
            }
        }

        return $this;
    }
}
