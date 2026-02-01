<?php

namespace App\Enums;

enum ProspectSource: string
{
    case COLD_EMAIL = 'cold_email';
    case LINKEDIN = 'linkedin';
    case NETWORK = 'network';
    case SEO = 'seo';
    case REFERRAL = 'referral';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::COLD_EMAIL => 'Cold Email',
            self::LINKEDIN => 'LinkedIn',
            self::NETWORK => 'Réseau',
            self::SEO => 'SEO / Inbound',
            self::REFERRAL => 'Recommandation',
            self::OTHER => 'Autre',
        };
    }
}
