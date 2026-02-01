<?php

namespace App\Filament\Widgets;

use App\Enums\ProspectStatus;
use App\Filament\Resources\ProspectResource;
use App\Models\Prospect;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProspectsToFollowUp extends BaseWidget
{
    protected static ?string $heading = 'À relancer aujourd\'hui';

    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Prospect::query()
                    ->needsAction()
                    ->orderBy('next_action_at', 'asc')
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Entreprise')
                    ->weight('bold')
                    ->searchable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(ProspectStatus $state): string => $state->label())
                    ->color(fn(ProspectStatus $state): string => $state->color()),

                Tables\Columns\TextColumn::make('next_action_at')
                    ->label('Prévu le')
                    ->dateTime('d/m/Y')
                    ->color('danger'),

                Tables\Columns\TextColumn::make('main_problem')
                    ->label('Contexte')
                    ->limit(50)
                    ->tooltip(fn(Prospect $record): ?string => $record->main_problem),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('Voir')
                    ->icon('heroicon-o-eye')
                    ->url(fn(Prospect $record): string => ProspectResource::getUrl('edit', ['record' => $record])),

                Tables\Actions\Action::make('done')
                    ->label('Fait')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->action(function (Prospect $record) {
                        $record->update([
                            'last_action_at' => now(),
                            'next_action_at' => null,
                        ]);
                    }),
            ])
            ->emptyStateHeading('Aucune relance prévue')
            ->emptyStateDescription('Tous vos prospects sont à jour !')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
}
