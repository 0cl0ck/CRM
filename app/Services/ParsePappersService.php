<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class ParsePappersService
{
    /**
     * Parse a Pappers PDF extract and return structured business data.
     *
     * Pappers extracts use a tab-delimited format: "Label\tValue"
     *
     * @param string $filePath Absolute path to the PDF file
     * @return array Associative array ready to fill Prospect model fields
     */
    public function parse(string $filePath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Normalize line endings
        $text = preg_replace('/\r\n/', "\n", $text);

        // Build a key-value map from tab-separated lines
        $kvMap = $this->buildKeyValueMap($text);

        $result = [];

        // --- Dénomination / Raison sociale ---
        $result['company_name'] = $kvMap['Dénomination ou raison sociale']
            ?? $kvMap['Dénomination']
            ?? $kvMap['Raison sociale']
            ?? null;

        // --- SIREN (from RCS number: "418 199 618 R.C.S. Dunkerque") ---
        $rcs = $kvMap['Immatriculation au RCS, numéro'] ?? $kvMap['Numéro RCS'] ?? null;
        if ($rcs && preg_match('/(\d[\d\s]{7,10}\d)\s*R\.?C\.?S/i', $rcs, $m)) {
            $result['siren'] = preg_replace('/\s/', '', $m[1]);
        }

        // --- Direct SIREN/SIRET if present ---
        if (isset($kvMap['SIREN'])) {
            $result['siren'] = preg_replace('/\s/', '', $kvMap['SIREN']);
        }
        if (isset($kvMap['SIRET'])) {
            $result['siret'] = preg_replace('/\s/', '', $kvMap['SIRET']);
        }

        // --- Code NAF / APE ---
        $nafField = $kvMap['Code NAF'] ?? $kvMap['Code APE'] ?? $kvMap['NAF'] ?? null;
        if ($nafField && preg_match('/(\d{2}\.?\d{2}[A-Z])/i', $nafField, $m)) {
            $result['naf_code'] = $m[1];
        }

        // --- Activité principale (from "Activités principales" or "Activité(s) exercée(s)") ---
        $activite = $kvMap['Activités principales'] ?? $kvMap['Activité(s) exercée(s)'] ?? null;
        if ($activite) {
            $result['naf_label'] = $this->sanitizeUtf8($activite);
        }

        // --- Forme juridique ---
        $result['legal_form'] = $kvMap['Forme juridique'] ?? null;

        // --- Capital social (e.g. "8 000,00 Euros") ---
        $capitalStr = $kvMap['Capital social'] ?? null;
        if ($capitalStr && preg_match('/([\d\s.,]+)\s*(?:€|EUR|Euros?)/i', $capitalStr, $m)) {
            $result['capital'] = $this->parseNumber($m[1]);
        }

        // --- Adresse → extract city from postal code pattern ---
        $address = $kvMap['Adresse du siège'] ?? $kvMap['Adresse'] ?? $kvMap['Siège social'] ?? null;
        if ($address && preg_match('/(\d{5})\s+([A-ZÀ-Ü][A-ZÀ-Ü\s\-]+)/i', $address, $m)) {
            $result['city'] = trim($m[2]);
        }

        // --- Date d'immatriculation → creation_date ---
        $dateStr = $kvMap["Date d'immatriculation"]
            ?? $kvMap['Date de commencement d\'activité']
            ?? null;
        if ($dateStr) {
            $result['creation_date'] = $this->parseDate($dateStr);
        }

        // --- Dirigeant (from "Nom, prénoms" field) ---
        $directorName = $kvMap['Nom, prénoms'] ?? null;
        if ($directorName) {
            $result['director_name'] = $this->sanitizeUtf8($directorName);
            $result['contact_name'] = $result['director_name'];
        }

        // --- Effectif ---
        $effectif = $kvMap['Effectif'] ?? $kvMap['Nombre de salariés'] ?? null;
        if ($effectif && preg_match('/(\d+)/', $effectif, $m)) {
            $result['employees'] = (int) $m[1];
        }

        // --- Chiffre d'affaires ---
        $ca = $kvMap["Chiffre d'affaires"] ?? $kvMap['CA'] ?? null;
        if ($ca && preg_match('/([\d\s.,]+)\s*(?:€|EUR|Euros?|k€|K€)/i', $ca, $m)) {
            $multiplier = preg_match('/k€|K€/i', $ca) ? 1000 : 1;
            $result['revenue'] = $this->parseNumber($m[1]) * $multiplier;
        }

        // Sanitize all strings and filter empty values
        $result = array_map(function ($v) {
            return is_string($v) ? $this->sanitizeUtf8($v) : $v;
        }, $result);

        return array_filter($result, fn($v) => $v !== null && $v !== '' && $v !== 0);
    }

    /**
     * Build a key-value map from tab-delimited PDF text.
     *
     * Handles multi-line values (continuation lines without a tab).
     */
    private function buildKeyValueMap(string $text): array
    {
        $map = [];
        $lines = explode("\n", $text);
        $lastKey = null;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Tab-separated: "Label\tValue"
            if (str_contains($line, "\t")) {
                $parts = explode("\t", $line, 2);
                $key = trim($parts[0]);
                $value = trim($parts[1] ?? '');

                if (!empty($key) && !empty($value)) {
                    $map[$key] = $value;
                    $lastKey = $key;
                }
            } elseif ($lastKey && isset($map[$lastKey])) {
                // Continuation of previous value (multi-line)
                $map[$lastKey] .= ' ' . $line;
            }
        }

        return $map;
    }

    /**
     * Parse a French-formatted number to int.
     */
    private function parseNumber(string $value): int
    {
        $value = preg_replace('/\s/', '', $value);
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);
        return (int) round((float) $value);
    }

    /**
     * Parse a French date string to Y-m-d format.
     */
    private function parseDate(string $dateStr): ?string
    {
        $dateStr = trim($dateStr);

        // Try dd/mm/yyyy or dd-mm-yyyy
        if (preg_match('|(\d{1,2})[/\-](\d{1,2})[/\-](\d{2,4})|', $dateStr, $m)) {
            try {
                $format = strlen($m[3]) === 4 ? 'd/m/Y' : 'd/m/y';
                $normalized = $m[1] . '/' . $m[2] . '/' . $m[3];
                return \Carbon\Carbon::createFromFormat($format, $normalized)->toDateString();
            } catch (\Exception) {
                // fall through
            }
        }

        // Try Carbon::parse as fallback
        try {
            return \Carbon\Carbon::parse($dateStr)->toDateString();
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Sanitize a string to valid UTF-8.
     */
    private function sanitizeUtf8(string $value): string
    {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $value);
        return trim($value);
    }
}
