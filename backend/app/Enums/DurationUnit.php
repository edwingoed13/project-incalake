<?php

namespace App\Enums;

enum DurationUnit: string
{
    case DAYS = 'days';
    case HOURS = 'hours';
    case MINUTES = 'minutes';

    public function getLabel(): string
    {
        return match($this) {
            self::DAYS => 'Días',
            self::HOURS => 'Horas',
            self::MINUTES => 'Minutos',
        };
    }

    public function toHours(float $quantity): float
    {
        return match($this) {
            self::DAYS => $quantity * 24,
            self::HOURS => $quantity,
            self::MINUTES => $quantity / 60,
        };
    }
}