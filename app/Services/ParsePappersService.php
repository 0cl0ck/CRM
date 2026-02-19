<?php

namespace App\Services;

use Smalot\PdfParser\Parser;

class ParsePappersService
{
    /**
     * Parse a Pappers PDF extract and return structured business data.
     *
     * @param string $filePath Absolute path to the PDF file
     * @return array Associative array ready to fill Prospect model fields
     */
    public function parse(string $filePath): array
    {
        if (!class_exists(\Smalot\PdfParser\Parser::class)) {
            throw new \RuntimeException('smalot/pdfparser n\'est pas installé. Lancez : composer require smalot/pdfparser');
        }

        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile($filePath);
        $text = $pdf->getText();

        // Normalize whitespace
        $text = preg_replace('/\r\n/', "\n", $text);

        $result = [];

        // Raison sociale / Dénomination
        $result['company_name'] = $this->extract($text, '/(?:D[ée]nomination|Raison sociale)\s*[:\-]?\s*(.+)/i');

        // SIREN (9 digits)
        if (preg_match('/(?:SIREN|N°\s*SIREN)\s*[:\-]?\s*(\d[\d\s]{7,10}\d)/i', $text, $m)) {
            $result['siren'] = preg_replace('/\s/', '', $m[1]);
        }

        // SIRET (14 digits)
        if (preg_match('/(?:SIRET|N°\s*SIRET)\s*[:\-]?\s*(\d[\d\s]{12,16}\d)/i', $text, $m)) {
            $result['siret'] = preg_replace('/\s/', '', $m[1]);
        }

        // Code NAF / APE
        if (preg_match('/(?:Code\s*(?:NAF|APE)|NAF|APE)\s*[:\-]?\s*(\d{2}\.?\d{2}[A-Z])/i', $text, $m)) {
            $result['naf_code'] = $m[1];
        }

        // NAF label (activité principale)
        $result['naf_label'] = $this->extract($text, '/(?:Activit[ée]\s*(?:principale)?|Libell[ée]\s*(?:NAF|APE))\s*[:\-]?\s*(.+)/i');

        // Forme juridique
        $result['legal_form'] = $this->extract($text, '/(?:Forme\s*juridique|Forme\s*l[ée]gale|Statut\s*juridique)\s*[:\-]?\s*(.+)/i');

        // Capital social
        if (preg_match('/(?:Capital\s*(?:social)?)\s*[:\-]?\s*([\d\s.,]+)\s*(?:€|EUR|euros?)/i', $text, $m)) {
            $result['capital'] = $this->parseNumber($m[1]);
        }

        // Chiffre d'affaires (last available)
        if (preg_match_all('/(?:Chiffre\s*d\'?affaires?|CA)\s*[:\-]?\s*([\d\s.,]+)\s*(?:€|EUR|euros?|k€|K€)/i', $text, $m)) {
            $last = end($m[1]);
            $multiplier = 1;
            // Check if the matched text contains k€ indicator
            $lastFullMatch = end($m[0]);
            if (preg_match('/k€|K€/i', $lastFullMatch)) {
                $multiplier = 1000;
            }
            $result['revenue'] = $this->parseNumber($last) * $multiplier;
        }

        // Effectif
        if (preg_match('/(?:Effectif|Nombre\s*(?:de\s*)?salari[ée]s?|Tranche\s*d\'?effectif)\s*[:\-]?\s*(\d+)/i', $text, $m)) {
            $result['employees'] = (int) $m[1];
        } elseif (preg_match('/(\d+)\s*(?:salari[ée]s?|employ[ée]s?)/i', $text, $m)) {
            $result['employees'] = (int) $m[1];
        }

        // Date de création
        if (preg_match('/(?:Date\s*(?:de\s*)?(?:cr[ée]ation|immatriculation|d[ée]but\s*d\'?activit[ée]))\s*[:\-]?\s*(\d{1,2}[\/-]\d{1,2}[\/-]\d{2,4})/i', $text, $m)) {
            try {
                $result['creation_date'] = \Carbon\Carbon::createFromFormat(
                    str_contains($m[1], '/') ? 'd/m/Y' : 'd-m-Y',
                    $m[1]
                )->toDateString();
            } catch (\Exception) {
                // Try other format
                try {
                    $result['creation_date'] = \Carbon\Carbon::parse($m[1])->toDateString();
                } catch (\Exception) {
                    // ignore
                }
            }
        }

        // Ville (from address)
        if (preg_match('/(?:Adresse|Si[èe]ge\s*social)\s*[:\-]?\s*.*?(\d{5})\s+([A-ZÀ-Ü][A-ZÀ-Ü\s\-]+)/i', $text, $m)) {
            $result['city'] = trim($m[2]);
        }

        // Dirigeant
        $result['director_name'] = $this->extract($text, '/(?:Dirigeant|G[ée]rant|Pr[ée]sident|Repr[ée]sentant\s*l[ée]gal)\s*[:\-]?\s*(?:M\.|Mme|Mr\.?)?\s*(.+)/i');

        // Contact name = director name by default
        if (!empty($result['director_name'])) {
            $result['contact_name'] = $result['director_name'];
        }

        // Filter out null/empty values
        return array_filter($result, fn($v) => $v !== null && $v !== '' && $v !== 0);
    }

    /**
     * Extract a value using a regex pattern.
     */
    private function extract(string $text, string $pattern): ?string
    {
        if (preg_match($pattern, $text, $m)) {
            return trim($m[1]);
        }

        return null;
    }

    /**
     * Parse a French-formatted number (spaces, commas, dots) to int.
     */
    private function parseNumber(string $value): int
    {
        // Remove spaces
        $value = preg_replace('/\s/', '', $value);
        // Handle French formatting: 1.234.567,89 → 1234567
        // or 1 234 567 → 1234567
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return (int) round((float) $value);
    }
}
