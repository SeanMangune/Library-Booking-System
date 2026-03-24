<?php

namespace App\Http\Controllers;

use App\Services\OcrSpaceClient;
use App\Services\QcIdOcrVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcIdVerificationController extends Controller
{
    public function __invoke(Request $request, QcIdOcrVerifier $verifier, OcrSpaceClient $ocrSpace): JsonResponse
    {
        $validated = $request->validate([
            'ocr_text' => 'nullable|string|max:12000',
            'user_name' => 'nullable|string|max:255',
            'qcid_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:25600',
        ]);

        $clientOcrText = trim((string) ($validated['ocr_text'] ?? ''));
        $ocrSpaceText = '';

        if ($request->hasFile('qcid_image')) {
            $apiResult = $ocrSpace->parseImage($request->file('qcid_image'));
            $ocrSpaceText = trim((string) ($apiResult['text'] ?? ''));
        }

        $combinedText = trim(implode("\n", array_filter([$clientOcrText, $ocrSpaceText])));
        if (mb_strlen($combinedText) < 20) {
            return response()->json([
                'success' => false,
                'message' => 'No readable text was detected. Please upload a clearer QC ID image.',
                'verification' => null,
                'ocr_text' => $combinedText,
            ], 200);
        }

        $verification = $verifier->verify(
            $combinedText,
            $validated['user_name'] ?? null,
        );

        if (! $verification['is_valid']) {
            $message = $verification['id_assessment'] === 'Fake QC ID'
                ? 'Fake QC ID detected. Please upload a genuine Quezon City Citizen ID.'
                : (! empty($verification['rejected_id_type'])
                    ? "This appears to be a {$verification['rejected_id_type']}. Only Quezon City Citizen IDs (QC IDs) are accepted."
                    : 'Only a Quezon City Citizen ID (QC ID) is accepted. Please upload a clearer QC ID image.');

            return response()->json([
                'success' => false,
                'message' => $message,
                'verification' => $verification,
                'ocr_text' => $combinedText,
            ], 200);  // 200 so client can still read the partial data
        }

        return response()->json([
            'success' => true,
            'message' => 'QC ID detected and verified successfully.',
            'verification' => $verification,
            'ocr_text' => $combinedText,
        ]);
    }
}
