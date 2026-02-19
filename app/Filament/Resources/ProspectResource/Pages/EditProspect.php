<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use App\Services\ParseLighthouseService;
use App\Services\ParsePappersService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EditProspect extends EditRecord
{
    protected static string $resource = ProspectResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['pappers_pdf'], $data['lighthouse_json']);
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('analyze_files')
                ->label('Analyser les fichiers')
                ->icon('heroicon-o-sparkles')
                ->color('primary')
                ->action(function () {
                    $data = $this->data;
                    $parsedFields = [];

                    $resolvePath = function ($fileState): ?string {
                        if (!$fileState)
                            return null;
                        while (is_array($fileState)) {
                            $fileState = reset($fileState);
                        }
                        if ($fileState instanceof TemporaryUploadedFile) {
                            return $fileState->getRealPath();
                        }
                        if (is_string($fileState) && !empty($fileState)) {
                            $path = storage_path('app/prospect-imports/' . $fileState);
                            if (file_exists($path))
                                return $path;
                            foreach (['private/livewire-tmp/', 'livewire-tmp/'] as $dir) {
                                $path = storage_path('app/' . $dir . $fileState);
                                if (file_exists($path))
                                    return $path;
                            }
                        }
                        return null;
                    };

                    $pappersPath = $resolvePath($data['pappers_pdf'] ?? null);
                    if ($pappersPath) {
                        try {
                            $parsedFields = array_merge($parsedFields, (new ParsePappersService())->parse($pappersPath));
                        } catch (\Exception $e) {
                            Notification::make()->title('Erreur PDF')->body($e->getMessage())->danger()->send();
                        }
                    }

                    $lhPath = $resolvePath($data['lighthouse_json'] ?? null);
                    if ($lhPath) {
                        try {
                            $parsedFields = array_merge($parsedFields, (new ParseLighthouseService())->parse($lhPath));
                        } catch (\Exception $e) {
                            Notification::make()->title('Erreur JSON')->body($e->getMessage())->danger()->send();
                        }
                    }

                    if (count($parsedFields) > 0) {
                        foreach ($parsedFields as $field => $value) {
                            $this->data[$field] = $value;
                        }
                        Notification::make()->title('Analyse terminée')->body(count($parsedFields) . ' champs pré-remplis')->success()->send();
                    } else {
                        Notification::make()->title('Aucun fichier analysé')->body('Veuillez d\'abord déposer un PDF Pappers et/ou un JSON Lighthouse.')->warning()->send();
                    }
                }),

            Actions\DeleteAction::make()->label('Supprimer'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
