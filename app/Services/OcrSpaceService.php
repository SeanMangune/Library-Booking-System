<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OcrSpaceService
{
    protected string $apiKey;
    protected string $apiUrl = 'https://api.ocr.space/parse/image';

    public function __construct()
    {
        $this->apiKey = config('services.ocr_space.key', env('OCR_SPACE_API_KEY', 'helloworld'));
    }

    /**
     * Scan a base64 encoded image using OCR.space API.
     *
     * @param string $base64Image Base64 encoded image string (with or without data prefix)
     * @return string|null The extracted text or null on failure
     */
    public function scanBase64(string $base64Image): ?string
    {
        try {
            // Remove data:image/...;base64, prefix if present
            if (preg_match('/^data:image\/(\w+);base64,/', $base64Image)) {
                $base64Image = preg_replace('/^data:image\/(\w+);base64,/', '', $base64Image);
            }

            $response = Http::asForm()->post($this->apiUrl, [
                'apikey' => $this->apiKey,
                'base64Image' => 'data:image/jpg;base64,' . $base64Image,
                'language' => 'eng',
                'isOverlayRequired' => false,
                'detectOrientation' => true,
                'scale' => true,
                'OCREngine' => 2, // Engine 2 is often better for IDs
            ]);

            if ($response->failed()) {
                Log::error('OCR.space API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            $data = $response->json();

            if (isset($data['IsErroredOnProcessing']) && $data['IsErroredOnProcessing']) {
                Log::error('OCR.space processing error', [
                    'errors' => $data['ErrorMessage'] ?? 'Unknown error',
                ]);
                return null;
            }

            $parsedResults = $data['ParsedResults'] ?? [];
            $fullText = '';

            foreach ($parsedResults as $result) {
                $fullText .= ($result['ParsedText'] ?? '') . "\n";
            }

            return trim($fullText) ?: null;

        } catch (\Exception $e) {
            Log::error('OCR.space service exception: ' . $e->getMessage());
            return null;
        }
    }
}
