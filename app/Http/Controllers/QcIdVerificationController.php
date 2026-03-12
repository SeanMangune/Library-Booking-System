<?php

namespace App\Http\Controllers;

use App\Services\QcIdOcrVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QcIdVerificationController extends Controller
{
    public function __invoke(Request $request, QcIdOcrVerifier $verifier): JsonResponse
    {
        $validated = $request->validate([
            'ocr_text' => 'required|string|min:20|max:12000',
            'user_name' => 'nullable|string|max:255',
        ]);

        $verification = $verifier->verify(
            $validated['ocr_text'],
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
            ], 200);  // 200 so client can still read the partial data
        }

        return response()->json([
            'success' => true,
            'message' => 'QC ID detected and verified successfully.',
            'verification' => $verification,
        ]);
    }
}
