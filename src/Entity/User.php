<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[UniqueEntity(fields: ['email'], message: 'There is already an account with this email')]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
#[UniqueEntity(fields: ['googleId'], message: 'This Google account is already linked')]
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
    private ?string $email = null;

    #[ORM\Column(nullable: true)]
    private ?string $password = null;

    #[ORM\Column(length: 20)]
    private string $role = 'user';

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $username = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $avatar = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $bio = null;

    #[ORM\Column(options: ['default' => false])]
    private bool $isBlocked = false;

    #[ORM\Column(options: ['default' => false])]
    private bool $isVerified = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;
    #[ORM\Column(length: 20, nullable: true)]
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

    public function hasLocalPassword(): bool
    {
        return $this->authProvider === 'local' && !empty($this->password);
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

}
