<?php

namespace App\Enum;

enum DifficulteEnum: string
{
    case FACILE = 'facile';
    case MOYEN = 'moyen';
    case DIFFICILE = 'difficile';
    case EXPERT = 'expert';

    public function getLabel(): string
    {
        return match($this) {
            self::FACILE => 'Facile',
            self::MOYEN => 'Moyen',
            self::DIFFICILE => 'Difficile',
            self::EXPERT => 'Expert',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::FACILE => 'Effort minimal, accessible à tous',
            self::MOYEN => 'Effort modéré, nécessite quelques compétences',
            self::DIFFICILE => 'Effort important, compétences avancées requises',
            self::EXPERT => 'Défi extrême, expertise nécessaire',
        };
    }

    public function getColor(): string
    {
        return match($this) {
            self::FACILE => '#18E3A4', // Vert
            self::MOYEN => '#FFE66D', // Jaune
            self::DIFFICILE => '#FF6B6B', // Orange/Rouge
            self::EXPERT => '#AA96DA', // Violet
        };
    }
}
