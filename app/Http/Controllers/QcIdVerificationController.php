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
            'qr_data' => 'nullable|string|max:5000',
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
                'message' => 'No readable text was detected. Please upload a QC ID Image.',
                'verification' => null,
                'ocr_text' => $combinedText,
            ], 200);
        }

        $verification = $verifier->verify(
            $combinedText,
            $validated['user_name'] ?? null,
        );

        // === QR Code Cross-Validation ===
        $qrData = trim((string) ($validated['qr_data'] ?? ''));
        $qrIdNumber = $this->extractQcIdFromQrData($qrData);

        if ($qrIdNumber !== null) {
            // QR is authoritative — override OCR-extracted ID
            $ocrId = $verification['id_number'] ?? null;

            if ($ocrId !== null && $ocrId !== $qrIdNumber) {
                // ID mismatch: QR wins, mark that correction happened
                $verification['id_number'] = $qrIdNumber;
                $verification['id_corrected_by_qr'] = true;
                $verification['ocr_id_before_correction'] = $ocrId;
            } elseif ($ocrId === null) {
                $verification['id_number'] = $qrIdNumber;
            }

            // QR validation confirms this is a real QC ID (it has a valid QR code)
            $verification['qr_validated'] = true;
        }

        if (! $verification['is_valid']) {
            if ($verification['id_assessment'] === 'Fake QC ID') {
                $message = 'Fake QC ID detected.';
                if (! empty($verification['fake_reason'])) {
                    $message .= ' ' . $verification['fake_reason'];
                }
                $message .= ' Please upload a genuine Quezon City Citizen ID.';
            } elseif (! empty($verification['rejected_id_type'])) {
                $rejectedType = $verification['rejected_id_type'];
                $message = "This ID is invalid because it's a {$rejectedType}. Only Quezon City Citizen IDs (QC IDs) are accepted.";
            } else {
                $message = 'Only a Quezon City Citizen ID (QC ID) is accepted. Please upload a QC ID Image.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'verification' => $verification,
                'ocr_text' => $combinedText,
                'qr_id_number' => $qrIdNumber,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'QC ID detected and verified successfully.',
            'verification' => $verification,
            'ocr_text' => $combinedText,
            'qr_id_number' => $qrIdNumber,
        ]);
    }

    /**
     * Extract a QC ID number from QR code data.
     * The QR may contain: a plain number, a URL, JSON, or key=value pairs.
     */
    private function extractQcIdFromQrData(string $qrData): ?string
    {
        if ($qrData === '') {
            return null;
        }

        // Pattern: 3-3-8 digit QC ID number with optional separators
        if (preg_match('/(\d{3})\s*(\d{3})\s*(\d{8})/', $qrData, $m)) {
            return $m[1] . ' ' . $m[2] . ' ' . $m[3];
        }

        // Continuous 14-digit number
        if (preg_match('/(\d{14})/', $qrData, $m)) {
            $d = $m[1];
            return substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6, 8);
        }

        // 13-digit number (missing leading zero)
        if (preg_match('/(\d{13})/', $qrData, $m)) {
            $d = '0' . $m[1];
            return substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6, 8);
        }

        // Any 10-14 digit number (from URL etc.)
        if (preg_match('/(\d{10,14})/', $qrData, $m)) {
            $d = str_pad($m[1], 14, '0', STR_PAD_LEFT);
            $d = substr($d, 0, 14);
            return substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6, 8);
        }

        return null;
    }
}
