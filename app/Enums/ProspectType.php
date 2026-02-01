<?php

namespace App\Enums;

enum ProspectType: string
{
    case ARTISAN = 'artisan';
    case PME = 'pme';
    case STARTUP = 'startup';
    case AGENCY = 'agency';
    case ECOMMERCE = 'ecommerce';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::ARTISAN => 'Artisan / Indépendant',
            self::PME => 'PME',
            self::STARTUP => 'Startup / SaaS',
            self::AGENCY => 'Agence',
            self::ECOMMERCE => 'E-commerce',
            self::OTHER => 'Autre',
        };
    }
}
