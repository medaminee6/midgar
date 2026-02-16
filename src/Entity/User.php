<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['username'], message: 'Ce nom d\'utilisateur est déjà pris')]
#[UniqueEntity(fields: ['googleId'], message: 'Ce compte Google est déjà lié')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_USER = 'ROLE_USER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    private const ROLE_MAPPING = [
        'user' => self::ROLE_USER,
        'admin' => self::ROLE_ADMIN,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank(message: 'L\'email est requis')]
    #[Assert\Email(message: 'Le format de l\'email est invalide')]
    #[Assert\Length(max: 180, maxMessage: 'L\'email ne peut pas dépasser {{ limit }} caractères')]
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private string $role = 'user';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom est requis')]
    #[Assert\Length(
        min: 2, 
        max: 50, 
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s-]+$/',
        message: 'Le nom ne peut contenir que des lettres, espaces et tirets'
    )]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le prénom est requis')]
    #[Assert\Length(
        min: 2, 
        max: 50, 
        minMessage: 'Le prénom doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le prénom ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-ZÀ-ÿ\s-]+$/',
        message: 'Le prénom ne peut contenir que des lettres, espaces et tirets'
    )]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    #[Assert\NotBlank(message: 'Le nom d\'utilisateur est requis')]
    #[Assert\Length(
        min: 3, 
        max: 20, 
        minMessage: 'Le nom d\'utilisateur doit contenir au moins {{ limit }} caractères',
        maxMessage: 'Le nom d\'utilisateur ne peut pas dépasser {{ limit }} caractères'
    )]
    #[Assert\Regex(
        pattern: '/^[a-zA-Z0-9]+$/',
        message: 'Le nom d\'utilisateur ne peut contenir que des lettres et des chiffres (sans espaces)'
    )]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\Length(max: 500, maxMessage: 'La bio ne peut pas dépasser {{ limit }} caractères')]
    private ?string $bio = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBlocked = false;

    #[ORM\Column(options: ['default' => true])]
    private bool $isVerified = true;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;
    
    #[ORM\Column(length: 20, nullable: true)]
    #[Assert\Regex(
        pattern: '/^[0-9+\-\s]+$/',
        message: 'Le numéro de téléphone n\'est pas valide'
    )]
    private ?string $phoneNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $resetToken = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $resetTokenExpiresAt = null;

    // ================= GOOGLE OAUTH =================
    #[ORM\Column(type: 'string', length: 255, nullable: true, unique: true)]
    private ?string $googleId = null;

    #[ORM\Column(type: 'string', length: 50)]
    private string $authProvider = 'local'; // 'local' ou 'google'

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $faceDescriptor = null;

    #[ORM\Column(type: 'boolean')]
    private bool $faceEnabled = false;

    // =================================================

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    // ================== IDENTIFIANTS ==================
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    // ================== PASSWORD ==================
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function eraseCredentials(): void
    {
        // nothing to clear
    }

    /**
     * Vérifie si l'utilisateur a un mot de passe local
     * Utilisé pour le reset de mot de passe
     */
    public function hasLocalPassword(): bool
    {
        // Un utilisateur peut réinitialiser son mot de passe s'il a un hash en base
        // Peu importe le provider (local ou google)
        return !empty($this->password);
    }

    // ================== ROLES ==================
    public function getRoles(): array
    {
        return [$this->getSymfonyRole()];
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setRole(string $role): static
    {
        if (in_array($role, ['user', 'admin'])) {
            $this->role = $role;
        } elseif (in_array($role, [self::ROLE_USER, self::ROLE_ADMIN])) {
            $this->role = $role === self::ROLE_ADMIN ? 'admin' : 'user';
        } else {
            throw new \InvalidArgumentException('Invalid role');
        }

        return $this;
    }

    public function getSymfonyRole(): string
    {
        return self::ROLE_MAPPING[$this->role] ?? self::ROLE_USER;
    }

    public function isAdmin(): bool
    {
        return $this->getSymfonyRole() === self::ROLE_ADMIN;
    }

    // ================== PROFIL ==================
    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;
        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): static
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getBio(): ?string
    {
        return $this->bio;
    }

    public function setBio(?string $bio): static
    {
        $this->bio = $bio;
        return $this;
    }

    // ================== STATUT ==================
    public function isBlocked(): bool
    {
        return $this->isBlocked;
    }

    public function setIsBlocked(bool $isBlocked): static
    {
        $this->isBlocked = $isBlocked;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;
        return $this;
    }

    // ================== GOOGLE ==================
    public function getGoogleId(): ?string
    {
        return $this->googleId;
    }

    public function setGoogleId(?string $googleId): static
    {
        $this->googleId = $googleId;
        return $this;
    }

    public function getAuthProvider(): string
    {
        return $this->authProvider;
    }

    public function setAuthProvider(string $authProvider): static
    {
        $this->authProvider = $authProvider;
        return $this;
    }

    // ================== DATE ==================
    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }
    
    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): self
    {
        $this->phoneNumber = $phoneNumber;
        return $this;
    }

    public function getResetToken(): ?string
    {
        return $this->resetToken;
    }

    public function setResetToken(?string $resetToken): self
    {
        $this->resetToken = $resetToken;
        return $this;
    }

    public function getResetTokenExpiresAt(): ?\DateTime
    {
        return $this->resetTokenExpiresAt;
    }

    public function setResetTokenExpiresAt(?\DateTime $date): self
    {
        $this->resetTokenExpiresAt = $date;
        return $this;
    }

    // ================== MÉTHODE UTILE ==================
    public function getFullName(): string
    {
        return trim($this->prenom . ' ' . $this->nom);
    }

    public function getFaceDescriptor(): ?string
    {
        return $this->faceDescriptor;
    }

    public function setFaceDescriptor(?string $faceDescriptor): self
    {
        $this->faceDescriptor = $faceDescriptor;
        return $this;
    }

    public function isFaceEnabled(): bool
    {
        return $this->faceEnabled;
    }

    public function setFaceEnabled(bool $faceEnabled): self
    {
        $this->faceEnabled = $faceEnabled;
        return $this;
    }
}