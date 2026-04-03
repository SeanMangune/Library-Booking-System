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
        // Increase maximum execution time specifically for this route
        set_time_limit(120);

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
        if (mb_strlen($combinedText) < 5) {
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

        // === QR Code Full Profile Extraction ===
        $qrData = trim((string) ($validated['qr_data'] ?? ''));
        $qrProfile = $this->extractProfileFromQrData($qrData);
        $qrIdNumber = $qrProfile['id_number'] ?? null;

        if ($qrProfile !== null) {
            // QR is authoritative — merge fields into verification result
            foreach ($qrProfile as $key => $value) {
                if ($value !== null && $value !== '') {
                    // Normalize dates if they came from the QR result
                    if (in_array($key, ['date_of_birth', 'date_issued', 'valid_until'])) {
                        $value = $verifier->normalizeDateToYmd($value);
                    }
                    
                    // Mark as corrected/validated by QR if it differs from OCR or was missing
                    if (($verification[$key] ?? null) !== $value) {
                        $verification[$key] = $value;
                        $verification['qr_overwritten_' . $key] = true;
                    }
                }
            }

            // QR validation confirms this is a real QC ID (contains a valid QR structure)
            $verification['qr_validated'] = true;
            $verification['qr_profile_extracted'] = true;
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
    /**
     * Extract a full profile from QR code data.
     * The QR may contain: JSON, urlencoded strings, or a plain QC ID number.
     *
     * @return array<string, string|null>|null
     */
    private function extractProfileFromQrData(string $qrData): ?array
    {
        if ($qrData === '') {
            return null;
        }

        $profile = [];

        // 1. Try JSON parsing
        if (str_starts_with($qrData, '{') || str_starts_with($qrData, '[')) {
            $data = json_decode($qrData, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                $mappings = [
                    'id_number' => ['qcid', 'id_number', 'id', 'qc_id', 'card_number', 'id_no', 'id_num'],
                    'cardholder_name' => ['full_name', 'name', 'cardholder_name', 'cardholder', 'card_holder', 'beneficiary', 'fullname'],
                    'sex' => ['sex', 'gender', 'sex_type'],
                    'date_of_birth' => ['date_of_birth', 'dob', 'birth_date', 'birthdate', 'bday', 'birth_day'],
                    'civil_status' => ['civil_status', 'status', 'marital_status'],
                    'date_issued' => ['date_issued', 'issued', 'issue_date'],
                    'valid_until' => ['valid_until', 'expiry', 'valid', 'expiry_date', 'expiration'],
                    'address' => ['address', 'residence', 'home_address', 'current_address', 'present_address', 'addr', 'permanent_address', 'location'],
                ];

                $findKeyRecursively = function ($data, $sKey) use (&$findKeyRecursively) {
                    if (!is_array($data)) return null;
                    foreach ($data as $k => $v) {
                        if (strtolower((string)$k) === strtolower($sKey)) return $v;
                        if (is_array($v)) {
                            $found = $findKeyRecursively($v, $sKey);
                            if ($found !== null) return $found;
                        }
                    }
                    return null;
                };

                foreach ($mappings as $targetKey => $sourceKeys) {
                    foreach ($sourceKeys as $sKey) {
                        $pVal = $findKeyRecursively($data, $sKey);
                        if (!empty($pVal)) {
                            $profile[$targetKey] = (string) $pVal;
                            $profile['_' . $targetKey . '_source'] = 'qr';
                            break;
                        }
                    }
                }

                // Aggressive QR Assembler: If address is missing, combine discrete fields
                if (empty($profile['address'])) {
                    $parts = [];
                    $addrKeys = [
                        'street' => ['street', 'st', 'st_name'],
                        'brgy' => ['barangay', 'brgy', 'brgy_name', 'bgy'],
                        'subd' => ['subdivision', 'subd', 'village', 'vil', 'sub'],
                        'block' => ['block', 'blk', 'blky'],
                        'lot' => ['lot', 'lt'],
                        'city' => ['city', 'city_name', 'munc'],
                    ];
                    
                    $assembled = [];
                    foreach ($addrKeys as $label => $keys) {
                        foreach ($keys as $k) {
                            $val = $findKeyRecursively($data, $k);
                            if (!empty($val)) {
                                $assembled[$label] = trim((string)$val);
                                break;
                            }
                        }
                    }

                    if (!empty($assembled)) {
                        $addrLine = '';
                        if (isset($assembled['block'])) $addrLine .= 'BLK-' . $assembled['block'] . ' ';
                        if (isset($assembled['lot'])) $addrLine .= 'LOT-' . $assembled['lot'] . ' ';
                        if (isset($assembled['street'])) $addrLine .= $assembled['street'] . ', ';
                        if (isset($assembled['subd'])) $addrLine .= $assembled['subd'] . ', ';
                        if (isset($assembled['brgy'])) $addrLine .= $assembled['brgy'] . ', ';
                        if (isset($assembled['city'])) $addrLine .= $assembled['city'];
                        else $addrLine .= 'QUEZON CITY';

                        $profile['address'] = trim($addrLine, ' ,');
                        $profile['_address_source'] = 'qr';
                    }
                }
            }
        }

        // 1b. Try pipe-separated strings (common on some cards: id|name|address|...)
        if (empty($profile) && str_contains($qrData, '|')) {
            $parts = explode('|', $qrData);
            if (count($parts) >= 2) {
                // Heuristic: if first part looks like a QC ID number, assume id|name|...
                $first = trim($parts[0]);
                if (preg_match('/^(\d{3})\D*(\d{3})\D*(\d{8})$/', $first) || preg_match('/^\d{13,16}$/', $first)) {
                    $profile['id_number'] = $first;
                    $profile['cardholder_name'] = trim($parts[1]);
                    $profile['_id_number_source'] = 'qr';
                    $profile['_cardholder_name_source'] = 'qr';
                    if (isset($parts[2])) {
                        $profile['address'] = trim($parts[2]);
                        $profile['_address_source'] = 'qr';
                    }
                    if (isset($parts[3])) {
                        $profile['sex'] = trim($parts[3]);
                        $profile['_sex_source'] = 'qr';
                    }
                    if (isset($parts[4])) {
                        $profile['date_of_birth'] = trim($parts[4]);
                        $profile['_date_of_birth_source'] = 'qr';
                    }
                }
            }
        }

        // 2. Try URL Query/Key-Value parsing (if it looks like key=value pair)
        if (empty($profile) && str_contains($qrData, '=') && !str_starts_with($qrData, '{')) {
            $parts = [];
            // Parse as query string
            parse_str(parse_url($qrData, PHP_URL_QUERY) ?? $qrData, $parts);
            
            if (!empty($parts)) {
                $mappings = [
                    'id_number' => ['qcid', 'id_number', 'id', 'qc_id', 'card_number'],
                    'cardholder_name' => ['name', 'full_name', 'cardholder', 'beneficiary'],
                    'sex' => ['sex', 'gender'],
                    'date_of_birth' => ['dob', 'birthdate', 'birth_date', 'bday'],
                    'civil_status' => ['status', 'civil_status'],
                    'address' => ['address', 'residence', 'home_address', 'current_address', 'present_address', 'addr', 'permanent_address', 'location'],
                    'date_issued' => ['issued', 'issue_date'],
                    'valid_until' => ['expiry', 'valid'],
                ];
                foreach ($mappings as $targetKey => $sourceKeys) {
                    foreach ($sourceKeys as $sKey) {
                        // Case-insensitive check for key in $parts
                        foreach ($parts as $k => $v) {
                            if (strtolower((string)$k) === strtolower($sKey) && !empty($v)) {
                                $profile[$targetKey] = (string) $v;
                                $profile['_' . $targetKey . '_source'] = 'qr';
                                break 2;
                            }
                        }
                    }
                }
            }
        }

        // 3. Fallback: Try plain QC ID number extraction
        if (empty($profile['id_number'])) {
            // Fallback: If no structured profile found, try a global regex search for QC ID patterns
            // specifically looking for 3-3-8 digit or 14-digit patterns in the raw string
            if (empty($profile['id_number'])) {
                if (preg_match('/(\d{3})\s*(\d{3})\s*(\d{8})/', $qrData, $m)) {
                    $profile['id_number'] = $m[1] . ' ' . $m[2] . ' ' . $m[3];
                    $profile['_id_number_source'] = 'qr';
                } elseif (preg_match('/(\d{14})/', $qrData, $m)) {
                    $d = $m[1];
                    $profile['id_number'] = substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6, 8);
                    $profile['_id_number_source'] = 'qr';
                } elseif (preg_match('/(\d{13})/', $qrData, $m)) {
                    $d = '0' . $m[1];
                    $profile['id_number'] = substr($d, 0, 3) . ' ' . substr($d, 3, 3) . ' ' . substr($d, 6, 8);
                    $profile['_id_number_source'] = 'qr';
                }
            }
        }

        return !empty($profile) ? $profile : null;
    }
}
