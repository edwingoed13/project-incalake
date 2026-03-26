<?php

namespace App\Enums;

enum TargetAudience: string
{
    case ALL = 'all';
    case FAMILIES = 'families';
    case ADULTS = 'adults';
    case ADVENTURE = 'adventure';
    case SENIORS = 'seniors';

    public function getLabel(): string
    {
        return match($this) {
            self::ALL => 'Todos',
            self::FAMILIES => 'Familias',
            self::ADULTS => 'Adultos',
            self::ADVENTURE => 'Aventureros',
            self::SENIORS => 'Adultos Mayores',
        };
    }
}