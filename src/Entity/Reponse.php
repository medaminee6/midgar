<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'reponses')]
class Reponse
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 500)]
    #[Assert\NotBlank(message: 'L\'option ne peut pas être vide.')]
    #[Assert\Length(min: 2, minMessage: 'L\'option doit contenir au minimum 2 caractères.')]
    private string $option = '';

    #[ORM\Column(type: 'string', length: 100)]
    #[Assert\NotBlank(message: 'Le tag ne peut pas être vide.')]
    #[Assert\Length(min: 2, minMessage: 'Le tag doit contenir au minimum 2 caractères.')]
    private string $tag = '';

    #[ORM\ManyToOne(targetEntity: Question::class, inversedBy: 'reponses')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Une réponse doit être associée à une question.')]
    private ?Question $question = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOption(): string
    {
        return $this->option;
    }

    public function setOption(string $option): self
    {
        $this->option = $option;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function setTag(string $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getQuestion(): ?Question
    {
        return $this->question;
    }

    public function setQuestion(?Question $question): self
    {
        $this->question = $question;
        return $this;
    }
}
