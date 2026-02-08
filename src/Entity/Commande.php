<?php

namespace App\Entity;

use App\Enum\CommandeEtat;
use App\Repository\CommandeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    #[Assert\NotNull(message: 'La quantité est obligatoire.')]
    #[Assert\GreaterThan(0, message: 'La quantité doit être supérieure à 0.')]
    private ?int $quantite = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date_commande = null;

    #[ORM\Column(type: 'string', enumType: CommandeEtat::class, length: 50)]
    #[Assert\NotNull(message: "L'état de la commande est obligatoire.")]
    private CommandeEtat $etat;


    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Le nom de l'acheteur est obligatoire.")]
    #[Assert\Length(max: 255)]
    private ?string $acheteur = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank(message: 'Le prix total est obligatoire.')]
    #[Assert\GreaterThanOrEqual(0, message: 'Le prix total doit être supérieur ou égal à 0.')]
    private ?string $prix_total = null;

    #[ORM\Column(length: 100, unique: true)]
    #[Assert\NotBlank(message: 'La référence est obligatoire.')]
    #[Assert\Length(max: 100)]
    private ?string $reference_commande = null;

    #[ORM\ManyToOne(targetEntity: Produit::class, inversedBy: 'commandes')]
    #[ORM\JoinColumn(name: 'produit_id', referencedColumnName: 'id', nullable: false)]
    private ?Produit $produit = null;

    public function __construct()
    {
        $this->date_commande = new \DateTime();
        $this->etat = CommandeEtat::EN_ATTENTE;
        $this->reference_commande = 'CMD-' . time() . '-' . mt_rand(1000, 9999);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuantite(): ?int
    {
        return $this->quantite;
    }

    public function setQuantite(int $quantite): self
    {
        $this->quantite = $quantite;

        return $this;
    }

    public function getDateCommande(): ?\DateTimeInterface
    {
        return $this->date_commande;
    }

    public function setDateCommande(\DateTimeInterface $date_commande): self
    {
        $this->date_commande = $date_commande;

        return $this;
    }

    public function getEtat(): CommandeEtat
    {
        return $this->etat;
    }

    public function setEtat(CommandeEtat $etat): self
    {
        $this->etat = $etat;

        return $this;
    }

    public function getAcheteur(): ?string
    {
        return $this->acheteur;
    }

    public function setAcheteur(string $acheteur): self
    {
        $this->acheteur = $acheteur;

        return $this;
    }

    public function getPrixTotal(): ?string
    {
        return $this->prix_total;
    }

    public function setPrixTotal(string $prix_total): self
    {
        $this->prix_total = $prix_total;

        return $this;
    }

    public function getReferenceCommande(): ?string
    {
        return $this->reference_commande;
    }

    public function setReferenceCommande(string $reference_commande): self
    {
        $this->reference_commande = $reference_commande;

        return $this;
    }

    public function getProduit(): ?Produit
    {
        return $this->produit;
    }

    public function setProduit(?Produit $produit): self
    {
        $this->produit = $produit;

        return $this;
    }
}
