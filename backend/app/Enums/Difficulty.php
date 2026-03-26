<?php

namespace App\Enums;

enum Difficulty: string
{
    case EASY = 'easy';
    case MODERATE = 'moderate';
    case HARD = 'hard';

    public function getLabel(): string
    {
        return match($this) {
            self::EASY => 'Fácil',
            self::MODERATE => 'Moderado',
            self::HARD => 'Difícil',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::EASY => 'fas fa-leaf',
            self::MODERATE => 'fas fa-walking',
            self::HARD => 'fas fa-mountain',
        };
    }

    public function getDescription(): string
    {
        return match($this) {
            self::EASY => 'Ideal para familias y principiantes',
            self::MODERATE => 'Requiere buena condición física',
            self::HARD => 'Para viajeros experimentados',
        };
    }
}