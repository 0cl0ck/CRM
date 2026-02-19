<?php

namespace App\Filament\Pages;

use App\Enums\Budget;
use App\Enums\ProspectSource;
use App\Enums\ProspectStatus;
use App\Enums\ProspectType;
use App\Models\Prospect;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Collection;
use Mokhosh\FilamentKanban\Pages\KanbanBoard;

class ProspectsKanbanBoard extends KanbanBoard
{
    protected static string $model = Prospect::class;

    protected static string $statusEnum = ProspectStatus::class;

    protected static string $recordTitleAttribute = 'company_name';

    protected static ?string $navigationIcon = 'heroicon-o-view-columns';

    protected static ?string $navigationLabel = 'Pipeline';

    protected static ?string $title = 'Pipeline Prospects';

    protected static ?int $navigationSort = 1;

    protected static string $recordView = 'prospect-kanban-record';

    protected static string $view = 'prospect-kanban-board';

    protected static string $statusView = 'prospect-kanban-status';

    protected static string $headerView = 'prospect-kanban-header';

    protected string $editModalTitle = 'Modifier le prospect';

    protected string $editModalSaveButtonLabel = 'Enregistrer';

    protected string $editModalCancelButtonLabel = 'Annuler';

    protected string $editModalWidth = '2xl';

    /**
     * Load only active prospects (exclude closed & trash by default)
     * Override to show all if needed
     */
    protected function records(): Collection
    {
        return Prospect::all();
    }

    /**
     * When a prospect is dragged to a new column
     */
    public function onStatusChanged(int|string $recordId, string $status, array $fromOrderedIds, array $toOrderedIds): void
    {
        Prospect::find($recordId)?->update([
            'status' => $status,
            'last_action_at' => now(),
        ]);
    }

    /**
     * When a prospect is reordered within the same column
     */
    public function onSortChanged(int|string $recordId, string $status, array $orderedIds): void
    {
        // No sort_order column yet — no-op
    }

    /**
     * Edit modal form schema
     */
    protected function getEditModalFormSchema(int|string|null $recordId): array
    {
        return [
            TextInput::make('company_name')
                ->label('Entreprise / Nom')
                ->required(),

            TextInput::make('contact_name')
                ->label('Contact'),

            TextInput::make('email')
                ->label('Email')
                ->email(),

            TextInput::make('phone')
                ->label('Téléphone')
                ->tel(),

            Select::make('status')
                ->label('Statut')
                ->options(collect(ProspectStatus::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()]))
                ->required(),

            Select::make('source')
                ->label('Source')
                ->options(collect(ProspectSource::cases())->mapWithKeys(fn($s) => [$s->value => $s->label()])),

            Select::make('budget')
                ->label('Budget estimé')
                ->options(collect(Budget::cases())->mapWithKeys(fn($b) => [$b->value => $b->label()])),

            Textarea::make('notes')
                ->label('Notes')
                ->rows(3),

            DateTimePicker::make('next_action_at')
                ->label('Prochaine action')
                ->displayFormat('d/m/Y H:i'),
        ];
    }

    /**
     * Handle edit form submission
     */
    protected function editRecord(int|string $recordId, array $data, array $state): void
    {
        Prospect::find($recordId)?->update($data);
    }
}
