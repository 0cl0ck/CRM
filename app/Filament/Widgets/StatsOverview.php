<?php

namespace App\Filament\Widgets;

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $total = Prospect::count();
        $identified = Prospect::where('status', ProspectStatus::IDENTIFIED)->count();
        $qualified = Prospect::where('status', ProspectStatus::QUALIFIED)->count();
        $won = Prospect::where('status', ProspectStatus::WON)->count();
        $lost = Prospect::where('status', ProspectStatus::LOST)->count();

        // Conversion rates
        $identifiedToQualified = $identified + $qualified + $won + $lost > 0
            ? round(($qualified + $won) / ($identified + $qualified + $won + $lost) * 100, 1)
            : 0;

        $qualifiedToWon = $qualified + $won > 0
            ? round($won / ($qualified + $won) * 100, 1)
            : 0;

        // Needs follow-up
        $needsAction = Prospect::needsAction()->count();

        return [
            Stat::make('Total Prospects', $total)
                ->description('Tous les prospects')
                ->icon('heroicon-o-user-group')
                ->color('primary'),

            Stat::make('Identifiés', $identified)
                ->description('En attente de qualification')
                ->icon('heroicon-o-eye')
                ->color('gray'),

            Stat::make('Qualifiés', $qualified)
                ->description('Prêts à approcher')
                ->icon('heroicon-o-check-badge')
                ->color('info'),

            Stat::make('Gagnés', $won)
                ->description('Clients convertis')
                ->icon('heroicon-o-trophy')
                ->color('success'),
        ];
    }
}
