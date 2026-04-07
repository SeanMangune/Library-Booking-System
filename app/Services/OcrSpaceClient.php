<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;

class OcrSpaceClient
{
    /**
     * @return array{success: bool, text: string, raw: array<string, mixed>}
     */
    public function parseImage(UploadedFile $image): array
    {
        $apiKey = (string) config('services.ocr_space.api_key');
        $endpoint = (string) config('services.ocr_space.endpoint');
        $language = (string) config('services.ocr_space.language', 'eng');

        if ($apiKey === '' || $endpoint === '') {
            return ['success' => false, 'text' => '', 'raw' => []];
        }

        $fileContents = file_get_contents($image->getRealPath()) ?: '';
        $fileName = $image->getClientOriginalName();

        // Engine 2: Best for photos/camera captures, handles rotated text.
        $engine2Text = $this->callOcrEngine($endpoint, $apiKey, $language, $fileContents, $fileName, '2', true);

        // Run engine 1 only when engine 2 result is weak/incomplete.
        // This keeps verification responsive on slow networks.
        $engine1Text = '';
        if ($this->shouldRunSecondaryEngine($engine2Text)) {
            $engine1Text = $this->callOcrEngine($endpoint, $apiKey, $language, $fileContents, $fileName, '1', true);
        }

        $text = $engine1Text === ''
            ? $engine2Text
            : $this->mergeOcrResults($engine1Text, $engine2Text);

        return [
            'success' => trim($text) !== '',
            'text' => trim($text),
            'raw' => [],
        ];
    }

    private function shouldRunSecondaryEngine(string $engine2Text): bool
    {
        $normalized = mb_strtoupper(trim($engine2Text));
        if ($normalized === '') {
            return true;
        }

        $labels = [
            'SEX', 'DATE OF BIRTH', 'CIVIL STATUS', 'DATE ISSUED',
            'VALID UNTIL', 'ADDRESS', 'CARDHOLDER', 'QUEZON',
            'CITIZEN', 'KASAMA',
        ];

        $hits = 0;
        foreach ($labels as $label) {
            if (str_contains($normalized, $label)) {
                $hits++;
            }
        }

        return $hits < 3 && mb_strlen($normalized) < 350;
    }

    /**
     * Intelligently merge OCR results from two engines.
     * Prefers the version with more field labels detected, falling back to length.
     */
    private function mergeOcrResults(string $engine1Text, string $engine2Text): string
    {
        if ($engine1Text === '' && $engine2Text === '') {
            return '';
        }
        if ($engine1Text === '') return $engine2Text;
        if ($engine2Text === '') return $engine1Text;

        // Count how many QC ID field labels each engine detected
        $labels = [
            'SEX', 'DATE OF BIRTH', 'CIVIL STATUS', 'DATE ISSUED',
            'VALID UNTIL', 'ADDRESS', 'CARDHOLDER', 'LAST NAME',
            'FIRST NAME', 'MIDDLE NAME', 'BLOOD TYPE', 'QUEZON',
            'CITIZEN', 'KASAMA', 'REPUBLIC',
        ];

        $e1Upper = mb_strtoupper($engine1Text);
        $e2Upper = mb_strtoupper($engine2Text);

        $e1Labels = 0;
        $e2Labels = 0;
        foreach ($labels as $label) {
            if (str_contains($e1Upper, $label)) $e1Labels++;
            if (str_contains($e2Upper, $label)) $e2Labels++;
        }

        // If one engine detected significantly more labels, use it as primary
        if ($e1Labels > $e2Labels + 2) {
            $primary = $engine1Text;
            $secondary = $engine2Text;
        } elseif ($e2Labels > $e1Labels + 2) {
            $primary = $engine2Text;
            $secondary = $engine1Text;
        } else {
            // Similar label counts — use the longer one as primary
            $primary = mb_strlen($engine1Text) >= mb_strlen($engine2Text) ? $engine1Text : $engine2Text;
            $secondary = $primary === $engine1Text ? $engine2Text : $engine1Text;
        }

        // Always append secondary for the verifier to have maximum data
        return trim($primary . "\n" . $secondary);
    }

    /**
     * Call OCR.space API with a specific engine.
     */
    private function callOcrEngine(
        string $endpoint,
        string $apiKey,
        string $language,
        string $fileContents,
        string $fileName,
        string $engine,
        bool $enableOverlay = false
    ): string {
        try {
            $response = Http::asMultipart()
                ->connectTimeout(8)
                ->timeout(20)
                ->withHeaders([
                    'apikey' => $apiKey,
                ])
                ->attach('file', $fileContents, $fileName)
                ->post($endpoint, [
                    'language' => $language,
                    'isOverlayRequired' => $enableOverlay ? 'true' : 'false',
                    'OCREngine' => $engine,
                    'scale' => 'true',
                    'isTable' => 'true',
                    'detectOrientation' => 'true',
                ]);

            if (! $response->ok()) {
                return '';
            }

            /** @var array<string, mixed> $body */
            $body = $response->json() ?? [];
            $parsedResults = $body['ParsedResults'] ?? [];
            $text = '';

            if (is_array($parsedResults)) {
                foreach ($parsedResults as $entry) {
                    if (is_array($entry) && ! empty($entry['ParsedText'])) {
                        $text .= "\n" . (string) $entry['ParsedText'];
                    }
                }
            }

            return trim($text);
        } catch (\Throwable) {
            return '';
        }
    }
}
