<?php

namespace App\Http\Controllers;

use App\Services\QcIdOcrVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcIdVerificationController extends Controller
{
    public function __invoke(Request $request, QcIdOcrVerifier $verifier, \App\Services\OcrSpaceService $ocrService): JsonResponse
    {
        $validated = $request->validate([
            'image_data' => 'required|string',
            'user_name' => 'nullable|string|max:255',
        ]);

        // 1. Perform OCR on the image data
        $ocrText = $ocrService->scanBase64($validated['image_data']);

        if (! $ocrText) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to read text from the image. Please ensure the photo is clear and well-lit.',
            ], 200);
        }

        // 2. Verify the extracted text
        $verification = $verifier->verify(
            $ocrText,
            $validated['user_name'] ?? null,
        );

        if (! $verification['is_valid']) {
            $message = ! empty($verification['rejected_id_type'])
                ? "This appears to be a {$verification['rejected_id_type']}. Only Quezon City Citizen IDs (QC IDs) are accepted."
                : 'Only a Quezon City Citizen ID (QC ID) is accepted. Please upload a clearer QC ID image.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'verification' => $verification,
                'ocr_text' => $ocrText, // Return text so frontend can store it
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'QC ID detected and verified successfully.',
            'verification' => $verification,
            'ocr_text' => $ocrText, // Return text so frontend can store it
        ]);
    }
}
