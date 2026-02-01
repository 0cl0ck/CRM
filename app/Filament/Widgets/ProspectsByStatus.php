<?php

namespace App\Filament\Widgets;

use App\Enums\ProspectStatus;
use App\Models\Prospect;
use Filament\Widgets\ChartWidget;

class ProspectsByStatus extends ChartWidget
{
    protected static ?string $heading = 'Répartition par statut';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $data = collect(ProspectStatus::cases())->map(function ($status) {
            return [
                'label' => $status->label(),
                'count' => Prospect::where('status', $status)->count(),
                'color' => $this->getColorForStatus($status),
            ];
        })->filter(fn($item) => $item['count'] > 0);

        return [
            'datasets' => [
                [
                    'label' => 'Prospects',
                    'data' => $data->pluck('count')->values()->toArray(),
                    'backgroundColor' => $data->pluck('color')->values()->toArray(),
                ],
            ],
            'labels' => $data->pluck('label')->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getColorForStatus(ProspectStatus $status): string
    {
        return match ($status) {
            ProspectStatus::IDENTIFIED => '#6B7280',    // gray
            ProspectStatus::QUALIFIED => '#3B82F6',     // blue
            ProspectStatus::CONTACTED => '#F59E0B',     // amber
            ProspectStatus::MEETING_SET => '#8B5CF6',   // purple
            ProspectStatus::PROPOSAL_SENT => '#F97316', // orange
            ProspectStatus::WON => '#10B981',           // green
            ProspectStatus::LOST => '#EF4444',          // red
        };
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
