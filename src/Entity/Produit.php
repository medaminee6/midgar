<?php

namespace App\Entity;

use App\Repository\ProduitRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ProduitRepository::class)]
#[ORM\Table(name: 'produit')]
class Produit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du produit est obligatoire.')]
    #[Assert\Length(max: 255)]
    private ?string $nom_produit = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix est obligatoire.')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le prix doit être supérieur ou égal à 0.')]
    private ?string $prix = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank(message: 'Le type de produit est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $type_produit = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité disponible est obligatoire.')]
    #[Assert\GreaterThanOrEqual(0, message: 'La quantité doit être supérieure ou égale à 0.')]
    private ?int $quantite_disponible = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_ajout = null;

    #[ORM\OneToMany(targetEntity: Commande::class, mappedBy: 'produit')]
    private Collection $commandes;

    public function __construct()
    {
        $this->commandes = new ArrayCollection();
        $this->date_ajout = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomProduit(): ?string
    {
        return $this->nom_produit;
    }

    public function setNomProduit(string $nom_produit): self
    {
        $this->nom_produit = $nom_produit;

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

    public function getPrix(): ?string
    {
        return $this->prix;
    }

    public function setPrix(string $prix): self
    {
        $this->prix = $prix;

        return $this;
    }

    public function getTypeProduit(): ?string
    {
        return $this->type_produit;
    }

    public function setTypeProduit(string $type_produit): self
    {
        $this->type_produit = $type_produit;

        return $this;
    }

    public function getQuantiteDisponible(): ?int
    {
        return $this->quantite_disponible;
    }

    public function setQuantiteDisponible(int $quantite_disponible): self
    {
        $this->quantite_disponible = $quantite_disponible;

        return $this;
    }

    public function getDateAjout(): ?\DateTimeInterface
    {
        return $this->date_ajout;
    }

    public function setDateAjout(\DateTimeInterface $date_ajout): self
    {
        $this->date_ajout = $date_ajout;

        return $this;
    }

    /**
     * @return Collection<int, Commande>
     */
    public function getCommandes(): Collection
    {
        return $this->commandes;
    }

    public function addCommande(Commande $commande): self
    {
        if (!$this->commandes->contains($commande)) {
            $this->commandes->add($commande);
            $commande->setProduit($this);
        }

        return $this;
    }

    public function removeCommande(Commande $commande): self
    {
        if ($this->commandes->removeElement($commande)) {
            // set the owning side to null (unless already changed)
            if ($commande->getProduit() === $this) {
                $commande->setProduit(null);
            }
        }

        return $this;
    }
}
