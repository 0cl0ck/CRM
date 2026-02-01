<?php

namespace App\Enums;

enum Budget: string
{
    case LOW = 'low';
    case MEDIUM = 'medium';
    case HIGH = 'high';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return match ($this) {
            self::LOW => '< 1 000 €',
            self::MEDIUM => '1 000 - 5 000 €',
            self::HIGH => '> 5 000 €',
            self::UNKNOWN => 'Non défini',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'gray',
            self::MEDIUM => 'warning',
            self::HIGH => 'success',
            self::UNKNOWN => 'gray',
        };
    }
}
