<?php

namespace App\Enum;

enum CommandeEtat: string
{
    case EN_ATTENTE = 'en_attente';
    case CONFIRMÉE = 'confirmée';
    case EXPÉDIÉE = 'expédiée';
    case LIVRÉE = 'livrée';
    case ANNULÉE = 'annulée';

    public function getLabel(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'En attente',
            self::CONFIRMÉE => 'Confirmée',
            self::EXPÉDIÉE => 'Expédiée',
            self::LIVRÉE => 'Livrée',
            self::ANNULÉE => 'Annulée',
        };
    }

    public function getBadgeClass(): string
    {
        return match($this) {
            self::EN_ATTENTE => 'bg-warning',
            self::CONFIRMÉE => 'bg-info',
            self::EXPÉDIÉE => 'bg-primary',
            self::LIVRÉE => 'bg-success',
            self::ANNULÉE => 'bg-danger',
        };
    }
}
