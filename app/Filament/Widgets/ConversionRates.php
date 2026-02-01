<?php

namespace App\Filament\Widgets;

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ConversionRates extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        // Count by status for funnel calculation
        $identified = Prospect::where('status', ProspectStatus::IDENTIFIED)->count();
        $qualified = Prospect::where('status', ProspectStatus::QUALIFIED)->count();
        $won = Prospect::where('status', ProspectStatus::WON)->count();
        $lost = Prospect::where('status', ProspectStatus::LOST)->count();

        // All prospects that have been processed (not just identified)
        $totalProcessed = $identified + $qualified + $won + $lost;

        // Taux Identifié → Qualifié: prospects who made it past identification
        $passedIdentification = $qualified + $won; // Those who got qualified (and potentially won)
        $identifiedToQualified = $totalProcessed > 0
            ? round($passedIdentification / $totalProcessed * 100, 1)
            : 0;

        // Taux Qualifié → Gagné: of those qualified, how many converted
        $qualifiedPool = $qualified + $won; // Currently qualified or already won
        $qualifiedToWon = $qualifiedPool > 0
            ? round($won / $qualifiedPool * 100, 1)
            : 0;

        // Taux global: won vs all
        $globalConversion = $totalProcessed > 0
            ? round($won / $totalProcessed * 100, 1)
            : 0;

        // Prospects needing action today
        $needsAction = Prospect::needsAction()->count();

        return [
            Stat::make('Identifié → Qualifié', $identifiedToQualified . '%')
                ->description($passedIdentification . ' / ' . $totalProcessed . ' prospects')
                ->icon('heroicon-o-arrow-trending-up')
                ->color($identifiedToQualified >= 50 ? 'success' : ($identifiedToQualified >= 25 ? 'warning' : 'danger')),

            Stat::make('Qualifié → Gagné', $qualifiedToWon . '%')
                ->description($won . ' / ' . $qualifiedPool . ' qualifiés')
                ->icon('heroicon-o-trophy')
                ->color($qualifiedToWon >= 30 ? 'success' : ($qualifiedToWon >= 15 ? 'warning' : 'danger')),

            Stat::make('Conversion globale', $globalConversion . '%')
                ->description('Du premier contact au closing')
                ->icon('heroicon-o-chart-bar')
                ->color($globalConversion >= 10 ? 'success' : ($globalConversion >= 5 ? 'warning' : 'gray')),

            Stat::make('À relancer', $needsAction)
                ->description('Actions en retard')
                ->icon('heroicon-o-bell-alert')
                ->color($needsAction > 0 ? 'danger' : 'success'),
        ];
    }
}
