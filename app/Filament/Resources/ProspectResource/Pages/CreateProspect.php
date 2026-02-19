<?php

namespace App\Filament\Resources\ProspectResource\Pages;

use App\Filament\Resources\ProspectResource;
use App\Services\ParseLighthouseService;
use App\Services\ParsePappersService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateProspect extends CreateRecord
{
    protected static string $resource = ProspectResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
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

                    // Parse Pappers PDF
                    $pappersPath = $resolvePath($data['pappers_pdf'] ?? null);
                    if ($pappersPath) {
                        try {
                            $parsed = (new ParsePappersService())->parse($pappersPath);
                            $parsedFields = array_merge($parsedFields, $parsed);
                        } catch (\Exception $e) {
                            Log::error('PDF parse error', ['error' => $e->getMessage()]);
                            Notification::make()->title('Erreur PDF')->body($e->getMessage())->danger()->send();
                        }
                    }

                    // Parse Lighthouse JSON
                    $lhPath = $resolvePath($data['lighthouse_json'] ?? null);
                    if ($lhPath) {
                        try {
                            $parsed = (new ParseLighthouseService())->parse($lhPath);
                            $parsedFields = array_merge($parsedFields, $parsed);
                        } catch (\Exception $e) {
                            Log::error('JSON parse error', ['error' => $e->getMessage()]);
                            Notification::make()->title('Erreur JSON')->body($e->getMessage())->danger()->send();
                        }
                    }

                    if (count($parsedFields) > 0) {
                        // Log every field and its type for debugging
                        foreach ($parsedFields as $field => $value) {
                            Log::info("Setting field: {$field}", [
                                'value' => $value,
                                'type' => gettype($value),
                                'json_ok' => json_encode($value) !== false,
                            ]);
                        }

                        // Test: can json_encode handle all parsed fields?
                        $jsonTest = json_encode($parsedFields);
                        if ($jsonTest === false) {
                            Log::error('json_encode FAILED on parsed fields', [
                                'error' => json_last_error_msg(),
                            ]);
                        } else {
                            Log::info('json_encode OK for parsed fields', ['json' => $jsonTest]);
                        }

                        // Test: can json_encode handle $this->data BEFORE modification?
                        $dataCopy = $this->data;
                        // Remove non-serializable file fields for test
                        unset($dataCopy['pappers_pdf'], $dataCopy['lighthouse_json']);
                        $jsonTest2 = json_encode($dataCopy);
                        if ($jsonTest2 === false) {
                            Log::error('json_encode FAILED on $this->data (sans files)', [
                                'error' => json_last_error_msg(),
                            ]);
                        } else {
                            Log::info('json_encode OK for $this->data (sans files)');
                        }

                        // Apply parsed values one by one
                        foreach ($parsedFields as $field => $value) {
                            $this->data[$field] = $value;
                        }

                        // Test: can json_encode handle $this->data AFTER modification?
                        $dataCopy2 = $this->data;
                        unset($dataCopy2['pappers_pdf'], $dataCopy2['lighthouse_json']);
                        $jsonTest3 = json_encode($dataCopy2);
                        if ($jsonTest3 === false) {
                            Log::error('json_encode FAILED on $this->data AFTER set', [
                                'error' => json_last_error_msg(),
                            ]);
                        } else {
                            Log::info('json_encode OK for $this->data AFTER set');
                        }

                        Notification::make()
                            ->title('Analyse terminée')
                            ->body(count($parsedFields) . ' champs pré-remplis')
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Aucun fichier analysé')
                            ->body('Veuillez d\'abord déposer un PDF Pappers et/ou un JSON Lighthouse.')
                            ->warning()
                            ->send();
                    }
                }),
        ];
    }
}
