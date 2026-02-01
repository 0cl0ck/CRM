<?php

namespace App\Enums;

enum ProspectStatus: string
{
    case IDENTIFIED = 'identified';
    case QUALIFIED = 'qualified';
    case CONTACTED = 'contacted';
    case MEETING_SET = 'meeting_set';
    case PROPOSAL_SENT = 'proposal_sent';
    case WON = 'won';
    case LOST = 'lost';

    public function label(): string
    {
        return match ($this) {
            self::IDENTIFIED => 'Identifié',
            self::QUALIFIED => 'Qualifié',
            self::CONTACTED => 'Contacté',
            self::MEETING_SET => 'RDV fixé',
            self::PROPOSAL_SENT => 'Proposition envoyée',
            self::WON => 'Gagné',
            self::LOST => 'Sans suite',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::IDENTIFIED => 'gray',
            self::QUALIFIED => 'info',
            self::CONTACTED => 'warning',
            self::MEETING_SET => 'primary',
            self::PROPOSAL_SENT => 'warning',
            self::WON => 'success',
            self::LOST => 'danger',
        };
    }
}
