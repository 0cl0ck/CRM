<?php

namespace App\Filament\Resources;

use App\Enums\Budget;
use App\Enums\ProspectSource;
use App\Enums\ProspectStatus;
use App\Enums\ProspectType;
use App\Filament\Resources\ProspectResource\Pages;
use App\Models\Prospect;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProspectResource extends Resource
{
    protected static ?string $model = Prospect::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Prospects';

    protected static ?string $modelLabel = 'Prospect';

    protected static ?string $pluralModelLabel = 'Prospects';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // ─── Import auto ───────────────────────────────────
                Forms\Components\Section::make('Import automatique')
                    ->description('Déposez un extrait Pappers (PDF) et/ou un rapport Lighthouse (JSON), puis cliquez sur "Analyser les fichiers" dans la barre d\'actions.')
                    ->schema([
                        Forms\Components\FileUpload::make('pappers_pdf')
                            ->label('Extrait Pappers (PDF)')
                            ->acceptedFileTypes(['application/pdf'])
                            ->disk('local')
                            ->directory('prospect-imports')
                            ->maxSize(5120),

                        Forms\Components\FileUpload::make('lighthouse_json')
                            ->label('Rapport Lighthouse (JSON)')
                            ->acceptedFileTypes(['application/json'])
                            ->disk('local')
                            ->directory('prospect-imports')
                            ->maxSize(10240),
                    ])
                    ->columns(2)
                    ->collapsible(),

                // ─── Informations ──────────────────────────────────
                Forms\Components\Section::make('Informations')
                    ->description('Données de contact du prospect')
                    ->schema([
                        Forms\Components\TextInput::make('company_name')
                            ->label('Entreprise / Nom')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2),

                        Forms\Components\TextInput::make('contact_name')
                            ->label('Contact')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Téléphone')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('website')
                            ->label('Site web')
                            ->url()
                            ->maxLength(255),
                    ])
                    ->columns(2),

                // ─── Données Entreprise ─────────────────────────────
                Forms\Components\Section::make('Données Entreprise')
                    ->description('Informations extraites de l\'extrait Pappers')
                    ->schema([
                        Forms\Components\TextInput::make('siren')
                            ->label('SIREN')
                            ->maxLength(9),

                        Forms\Components\TextInput::make('siret')
                            ->label('SIRET')
                            ->maxLength(14),

                        Forms\Components\TextInput::make('naf_code')
                            ->label('Code NAF')
                            ->maxLength(10),

                        Forms\Components\TextInput::make('naf_label')
                            ->label('Activité (libellé NAF)')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('legal_form')
                            ->label('Forme juridique')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('capital')
                            ->label('Capital social (€)')
                            ->numeric()
                            ->suffix('€'),

                        Forms\Components\TextInput::make('revenue')
                            ->label('Chiffre d\'affaires (€)')
                            ->numeric()
                            ->suffix('€'),

                        Forms\Components\TextInput::make('employees')
                            ->label('Effectif')
                            ->numeric(),

                        Forms\Components\DatePicker::make('creation_date')
                            ->label('Date de création')
                            ->displayFormat('d/m/Y'),

                        Forms\Components\TextInput::make('city')
                            ->label('Ville')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('director_name')
                            ->label('Dirigeant')
                            ->maxLength(255)
                            ->columnSpan(2),
                    ])
                    ->columns(3)
                    ->collapsible(),

                // ─── Classification ────────────────────────────────
                Forms\Components\Section::make('Classification')
                    ->description('Catégorisation et statut du prospect')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Statut')
                            ->options(collect(ProspectStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()]))
                            ->default(ProspectStatus::IDENTIFIED->value)
                            ->required(),

                        Forms\Components\Select::make('source')
                            ->label('Source')
                            ->options(collect(ProspectSource::cases())->mapWithKeys(fn($source) => [$source->value => $source->label()]))
                            ->default(ProspectSource::OTHER->value)
                            ->required(),

                        Forms\Components\Select::make('type')
                            ->label('Type')
                            ->options(collect(ProspectType::cases())->mapWithKeys(fn($type) => [$type->value => $type->label()]))
                            ->default(ProspectType::OTHER->value),

                        Forms\Components\Select::make('budget')
                            ->label('Budget estimé')
                            ->options(collect(Budget::cases())->mapWithKeys(fn($budget) => [$budget->value => $budget->label()]))
                            ->default(Budget::UNKNOWN->value),

                        Forms\Components\Select::make('urgency')
                            ->label('Urgence')
                            ->options([
                                1 => '1 - Très faible',
                                2 => '2 - Faible',
                                3 => '3 - Moyenne',
                                4 => '4 - Haute',
                                5 => '5 - Très haute',
                            ])
                            ->default(3),
                    ])
                    ->columns(3),

                // ─── Diagnostic Web (Lighthouse) ───────────────────
                Forms\Components\Section::make('Diagnostic Web (Lighthouse)')
                    ->description('Scores extraits du rapport Lighthouse')
                    ->schema([
                        Forms\Components\TextInput::make('lh_performance')
                            ->label('Performance')
                            ->numeric()
                            ->suffix('/100')
                            ->extraAttributes(fn($state) => [
                                'class' => match (true) {
                                    $state >= 90 => 'text-green-600',
                                    $state >= 50 => 'text-orange-500',
                                    default => 'text-red-600',
                                }
                            ]),

                        Forms\Components\TextInput::make('lh_accessibility')
                            ->label('Accessibilité')
                            ->numeric()
                            ->suffix('/100'),

                        Forms\Components\TextInput::make('lh_best_practices')
                            ->label('Bonnes pratiques')
                            ->numeric()
                            ->suffix('/100'),

                        Forms\Components\TextInput::make('lh_seo')
                            ->label('SEO')
                            ->numeric()
                            ->suffix('/100'),

                        Forms\Components\TextInput::make('lh_fcp')
                            ->label('FCP (First Contentful Paint)')
                            ->numeric()
                            ->suffix('s'),

                        Forms\Components\TextInput::make('lh_lcp')
                            ->label('LCP (Largest Contentful Paint)')
                            ->numeric()
                            ->suffix('s'),

                        Forms\Components\TextInput::make('lh_tbt')
                            ->label('TBT (Total Blocking Time)')
                            ->numeric()
                            ->suffix('ms'),

                        Forms\Components\TextInput::make('lh_cls')
                            ->label('CLS (Cumulative Layout Shift)')
                            ->numeric(),

                        Forms\Components\DatePicker::make('lh_report_date')
                            ->label('Date du rapport')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->columns(4)
                    ->collapsible(),

                // ─── Contexte ──────────────────────────────────────
                Forms\Components\Section::make('Contexte')
                    ->schema([
                        Forms\Components\Textarea::make('main_problem')
                            ->label('Problème principal')
                            ->placeholder('Quel est le problème ou besoin identifié ?')
                            ->rows(3)
                            ->columnSpanFull(),

                        Forms\Components\Textarea::make('notes')
                            ->label('Notes')
                            ->placeholder('Observations, historique des échanges...')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),

                // ─── Suivi ─────────────────────────────────────────
                Forms\Components\Section::make('Suivi')
                    ->description('Dates de relance')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_action_at')
                            ->label('Dernière action')
                            ->displayFormat('d/m/Y H:i'),

                        Forms\Components\DateTimePicker::make('next_action_at')
                            ->label('Prochaine action')
                            ->displayFormat('d/m/Y H:i'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company_name')
                    ->label('Entreprise')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->limit(25)
                    ->tooltip(fn(Prospect $record): string => $record->company_name)
                    ->description(fn(Prospect $record): ?string => $record->city),

                Tables\Columns\TextColumn::make('status')
                    ->label('Statut')
                    ->badge()
                    ->formatStateUsing(fn(ProspectStatus $state): string => $state->label())
                    ->color(fn(ProspectStatus $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn(ProspectType $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('budget')
                    ->label('Budget')
                    ->badge()
                    ->formatStateUsing(fn(Budget $state): string => $state->label())
                    ->color(fn(Budget $state): string => $state->color())
                    ->sortable(),

                Tables\Columns\TextColumn::make('revenue')
                    ->label('CA')
                    ->money('EUR', locale: 'fr')
                    ->sortable(),

                Tables\Columns\TextColumn::make('employees')
                    ->label('Effectif')
                    ->sortable(),

                Tables\Columns\TextColumn::make('lh_performance')
                    ->label('Perf. LH')
                    ->badge()
                    ->formatStateUsing(fn($state): string => $state ? $state . '/100' : '—')
                    ->color(fn($state): string => match (true) {
                        !$state => 'gray',
                        $state >= 90 => 'success',
                        $state >= 50 => 'warning',
                        default => 'danger',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('source')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn(ProspectSource $state): string => $state->label())
                    ->sortable(),

                Tables\Columns\TextColumn::make('next_action_at')
                    ->label('Action')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->color(fn(Prospect $record): string => $record->isOverdue() ? 'danger' : 'gray'),

                Tables\Columns\TextColumn::make('contact_name')
                    ->label('Contact')
                    ->searchable()
                    ->limit(20)
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('city')
                    ->label('Ville')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Créé le')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Statut')
                    ->options(collect(ProspectStatus::cases())->mapWithKeys(fn($status) => [$status->value => $status->label()])),

                Tables\Filters\SelectFilter::make('source')
                    ->label('Source')
                    ->options(collect(ProspectSource::cases())->mapWithKeys(fn($source) => [$source->value => $source->label()])),

                Tables\Filters\Filter::make('needs_action')
                    ->label('À relancer')
                    ->query(fn(Builder $query): Builder => $query->needsAction()),

                Tables\Filters\Filter::make('active')
                    ->label('Actifs uniquement')
                    ->query(fn(Builder $query): Builder => $query->active())
                    ->default(),
            ])
            ->actions([
                Tables\Actions\Action::make('qualify')
                    ->label('Qualifier')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Prospect $record): bool => $record->status === ProspectStatus::IDENTIFIED)
                    ->action(fn(Prospect $record) => $record->update([
                        'status' => ProspectStatus::QUALIFIED,
                        'last_action_at' => now(),
                    ])),

                Tables\Actions\Action::make('mark_lost')
                    ->label('Sans suite')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Prospect $record): bool => !in_array($record->status, [ProspectStatus::WON, ProspectStatus::LOST]))
                    ->requiresConfirmation()
                    ->action(fn(Prospect $record) => $record->update([
                        'status' => ProspectStatus::LOST,
                        'last_action_at' => now(),
                    ])),

                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProspects::route('/'),
            'create' => Pages\CreateProspect::route('/create'),
            'edit' => Pages\EditProspect::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::active()->count();
    }
}
