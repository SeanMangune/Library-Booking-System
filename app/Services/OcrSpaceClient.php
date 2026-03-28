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

        try {
            $response = Http::asMultipart()
                ->timeout(25)
                ->withHeaders([
                    'apikey' => $apiKey,
                ])
                ->attach(
                    'file',
                    file_get_contents($image->getRealPath()) ?: '',
                    $image->getClientOriginalName()
                )
                ->post($endpoint, [
                    'language' => $language,
                    'isOverlayRequired' => 'false',
                    'OCREngine' => '2',
                    'scale' => 'true',
                    'isTable' => 'false',
                ]);

            if (! $response->ok()) {
                return ['success' => false, 'text' => '', 'raw' => []];
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

            return [
                'success' => trim($text) !== '',
                'text' => trim($text),
                'raw' => $body,
            ];
        } catch (\Throwable) {
            return ['success' => false, 'text' => '', 'raw' => []];
        }
    }
}
