<?php

namespace App\Enums;

enum ServiceType: string
{
    case TOUR = 'tour';
    case PACKAGE = 'package';
    case EXPERIENCE = 'experience';
    case TRANSPORT = 'transport';

    public function getLabel(): string
    {
        return match($this) {
            self::TOUR => 'Tour',
            self::PACKAGE => 'Paquete',
            self::EXPERIENCE => 'Experiencia',
            self::TRANSPORT => 'Transporte',
        };
    }
}