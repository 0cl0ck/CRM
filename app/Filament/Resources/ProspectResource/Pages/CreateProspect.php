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

                        // Auto-generate main_problem from Lighthouse weak points
                        $this->data['main_problem'] = $this->generateMainProblem($this->data);
                        // Auto-generate notes from company data summary
                        $this->data['notes'] = $this->generateNotes($this->data);

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

    /**
     * Auto-generate a "Problème principal" from Lighthouse scores.
     */
    private function generateMainProblem(array $data): string
    {
        $problems = [];

        // Lighthouse score analysis
        $scores = [
            'Performance' => $data['lh_performance'] ?? null,
            'Accessibilité' => $data['lh_accessibility'] ?? null,
            'Bonnes pratiques' => $data['lh_best_practices'] ?? null,
            'SEO' => $data['lh_seo'] ?? null,
        ];

        $weakScores = [];
        $criticalScores = [];
        foreach ($scores as $label => $score) {
            if ($score === null)
                continue;
            if ($score < 50) {
                $criticalScores[] = "$label ($score/100)";
            } elseif ($score < 80) {
                $weakScores[] = "$label ($score/100)";
            }
        }

        if (count($criticalScores) > 0) {
            $problems[] = 'Scores critiques : ' . implode(', ', $criticalScores);
        }
        if (count($weakScores) > 0) {
            $problems[] = 'Scores faibles : ' . implode(', ', $weakScores);
        }

        // Web vitals analysis
        $lcp = $data['lh_lcp'] ?? null;
        $fcp = $data['lh_fcp'] ?? null;
        $tbt = $data['lh_tbt'] ?? null;
        $cls = $data['lh_cls'] ?? null;

        $vitalsIssues = [];
        if ($lcp !== null && $lcp > 2.5) {
            $vitalsIssues[] = "LCP lent ({$lcp}s, objectif < 2.5s)";
        }
        if ($fcp !== null && $fcp > 1.8) {
            $vitalsIssues[] = "FCP lent ({$fcp}s, objectif < 1.8s)";
        }
        if ($tbt !== null && $tbt > 200) {
            $vitalsIssues[] = "TBT élevé ({$tbt}ms, objectif < 200ms)";
        }
        if ($cls !== null && $cls > 0.1) {
            $vitalsIssues[] = "CLS instable ({$cls}, objectif < 0.1)";
        }

        if (count($vitalsIssues) > 0) {
            $problems[] = 'Web Vitals : ' . implode(', ', $vitalsIssues);
        }

        if (empty($problems)) {
            // Check if we have any LH data at all
            $hasLhData = ($data['lh_performance'] ?? null) !== null;
            if ($hasLhData) {
                return 'Scores Lighthouse corrects. Vérifier le contenu, le référencement local et la stratégie d\'acquisition.';
            }
            return '';
        }

        return "Site web sous-performant.\n" . implode("\n", $problems);
    }

    /**
     * Auto-generate "Notes" from company data.
     */
    private function generateNotes(array $data): string
    {
        $lines = [];

        $companyName = $data['company_name'] ?? null;
        $naf = $data['naf_label'] ?? null;
        $city = $data['city'] ?? null;
        $legalForm = $data['legal_form'] ?? null;
        $employees = $data['employees'] ?? null;
        $revenue = $data['revenue'] ?? null;
        $creationDate = $data['creation_date'] ?? null;
        $website = $data['website'] ?? null;

        // Company profile line
        $profile = [];
        if ($naf)
            $profile[] = $naf;
        if ($city)
            $profile[] = $city;
        if ($legalForm)
            $profile[] = $legalForm;
        if (count($profile) > 0) {
            $lines[] = 'Activité : ' . implode(' — ', $profile);
        }

        // Size indicators
        $size = [];
        if ($employees)
            $size[] = "$employees salariés";
        if ($revenue)
            $size[] = number_format($revenue, 0, ',', ' ') . ' € CA';
        if ($creationDate) {
            $year = substr($creationDate, 0, 4);
            if ($year)
                $size[] = "créée en $year";
        }
        if (count($size) > 0) {
            $lines[] = 'Profil : ' . implode(', ', $size);
        }

        // Website
        if ($website) {
            $lines[] = "Site actuel : $website";
        }

        // Lighthouse summary
        $perf = $data['lh_performance'] ?? null;
        $seo = $data['lh_seo'] ?? null;
        if ($perf !== null && $seo !== null) {
            $lines[] = "Lighthouse : Perf $perf/100, SEO $seo/100";
        }

        return implode("\n", $lines);
    }
}
