<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case PAYPAL = 'paypal';
    case CULQI = 'culqi';
    case ALL = 'all';

    public function getLabel(): string
    {
        return match($this) {
            self::PAYPAL => 'PayPal',
            self::CULQI => 'Culqi',
            self::ALL => 'Todos los métodos',
        };
    }

    public function getIcon(): string
    {
        return match($this) {
            self::PAYPAL => 'fab fa-paypal',
            self::CULQI => 'fas fa-credit-card',
            self::ALL => 'fas fa-credit-card',
        };
    }
}