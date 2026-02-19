<?php

namespace App\Services;

class ParseLighthouseService
{
    /**
     * Parse a Lighthouse JSON report file and extract key scores + Core Web Vitals.
     *
     * @param string $filePath Absolute path to the .json file
     * @return array Associative array ready to fill Prospect model fields
     */
    public function parse(string $filePath): array
    {
        $json = file_get_contents($filePath);
        $data = json_decode($json, true);

        if (!$data || !isset($data['categories'])) {
            return [];
        }

        $result = [];

        // Category scores (0.0-1.0 → 0-100)
        $categories = $data['categories'] ?? [];
        $result['lh_performance'] = $this->scoreToInt($categories['performance']['score'] ?? null);
        $result['lh_accessibility'] = $this->scoreToInt($categories['accessibility']['score'] ?? null);
        $result['lh_best_practices'] = $this->scoreToInt($categories['best-practices']['score'] ?? null);
        $result['lh_seo'] = $this->scoreToInt($categories['seo']['score'] ?? null);

        // Core Web Vitals from audits
        $audits = $data['audits'] ?? [];
        $result['lh_fcp'] = $this->msToSeconds($audits['first-contentful-paint']['numericValue'] ?? null);
        $result['lh_lcp'] = $this->msToSeconds($audits['largest-contentful-paint']['numericValue'] ?? null);
        $result['lh_tbt'] = round($audits['total-blocking-time']['numericValue'] ?? 0);
        $result['lh_cls'] = round($audits['cumulative-layout-shift']['numericValue'] ?? 0, 3);

        // Report date
        if (isset($data['fetchTime'])) {
            try {
                $result['lh_report_date'] = \Carbon\Carbon::parse($data['fetchTime'])->toDateString();
            } catch (\Exception) {
                // ignore
            }
        }

        // Also extract website URL if available
        $result['website'] = $data['finalDisplayedUrl'] ?? $data['requestedUrl'] ?? null;

        return $result;
    }

    private function scoreToInt(?float $score): ?int
    {
        if ($score === null) {
            return null;
        }

        return (int) round($score * 100);
    }

    private function msToSeconds(?float $ms): ?float
    {
        if ($ms === null) {
            return null;
        }

        return round($ms / 1000, 1);
    }
}
