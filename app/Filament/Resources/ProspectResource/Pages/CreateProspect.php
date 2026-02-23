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
                    $rawData = $this->data;
                    $parsedFields = [];

                    // --- Parse Pappers PDF ---
                    $pappersPath = $this->resolveUploadedFilePath($rawData['pappers_pdf'] ?? null);
                    if ($pappersPath) {
                        try {
                            $parsed = (new ParsePappersService())->parse($pappersPath);
                            $parsedFields = array_merge($parsedFields, $parsed);
                        } catch (\Exception $e) {
                            Log::error('PDF parse error', ['error' => $e->getMessage()]);
                            Notification::make()->title('Erreur PDF')->body($e->getMessage())->danger()->send();
                        }
                    }

                    // --- Parse Lighthouse JSON ---
                    $lhPath = $this->resolveUploadedFilePath($rawData['lighthouse_json'] ?? null);
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
                        // Ensure all values are JSON-safe before setting them
                        foreach ($parsedFields as $field => $value) {
                            if (is_string($value)) {
                                $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
                            }
                            $this->data[$field] = $value;
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

    /**
     * Resolve a Livewire FileUpload state to an absolute file path.
     */
    private function resolveUploadedFilePath(mixed $fileState): ?string
    {
        if (!$fileState) {
            return null;
        }

        // Unwrap arrays (Livewire nests the value as UUID => TemporaryUploadedFile)
        while (is_array($fileState)) {
            if (empty($fileState)) {
                return null;
            }
            $fileState = reset($fileState);
        }

        // Direct TemporaryUploadedFile object
        if ($fileState instanceof TemporaryUploadedFile) {
            $path = $fileState->getRealPath();
            return file_exists($path) ? $path : null;
        }

        // String filename fallback
        if (is_string($fileState) && !empty($fileState)) {
            // Try creating from Livewire's internal tracking
            try {
                $tmpFile = TemporaryUploadedFile::createFromLivewire($fileState);
                $path = $tmpFile->getRealPath();
                if (file_exists($path)) {
                    return $path;
                }
            } catch (\Exception) {
                // Fall through to filesystem search
            }

            // Search filesystem directly
            foreach (['prospect-imports', 'private/livewire-tmp', 'livewire-tmp'] as $dir) {
                $path = storage_path('app/' . $dir . '/' . $fileState);
                if (file_exists($path)) {
                    return $path;
                }
            }
        }

        return null;
    }
}
