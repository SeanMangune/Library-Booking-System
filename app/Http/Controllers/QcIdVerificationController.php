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
        // Text length checks removed to allow all images to cleanly fail as INVALID through the verifier.

        $verification = $verifier->verify(
            $combinedText,
            $validated['user_name'] ?? null,
        );
        $ocrAddressCandidate = $this->normalizeAddressText((string) ($verification['address'] ?? ''));
        $ocrNameCandidate = $this->normalizeEnyeCharacters((string) ($verification['cardholder_name'] ?? ''));

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
            
            // CRITICAL: If QR is validated, the ID is considered Verified regardless of OCR score
            $verification['is_valid'] = true;
            $verification['id_assessment'] = 'Verified';
        }

        // Preserve the richest cardholder name variant. If QR drops the
        // tilde on Ñ but OCR captures it, keep the OCR version.
        $qrNameCandidate = $this->normalizeEnyeCharacters((string) ($verification['cardholder_name'] ?? ''));
        $bestCardholderName = $this->chooseBestCardholderName([
            $qrNameCandidate,
            $ocrNameCandidate,
        ]);

        if ($bestCardholderName !== '') {
            $verification['cardholder_name'] = $bestCardholderName;

            if (
                $qrProfile !== null
                && $ocrNameCandidate !== ''
                && mb_stripos($ocrNameCandidate, 'Ñ') !== false
                && ($qrNameCandidate === '' || mb_stripos($qrNameCandidate, 'Ñ') === false)
                && $bestCardholderName === $ocrNameCandidate
            ) {
                $verification['_cardholder_name_source'] = 'ocr_enye_fallback';
                $verification['qr_name_missing_enye'] = true;
            }
        }

        // Keep the richest plausible address available. QR may sometimes
        // provide only a short address while OCR has more detail.
        $qrAddressCandidate = $this->normalizeAddressText((string) ($verification['address'] ?? ''));
        $textAddressCandidate = $this->extractAddressFromRawText($combinedText);
        $bestAddress = $this->chooseBestAddress([
            $qrAddressCandidate,
            $ocrAddressCandidate,
            $textAddressCandidate,
        ]);

        if (
            $qrProfile !== null
            && $qrAddressCandidate !== ''
            && $bestAddress !== ''
            && $bestAddress !== $qrAddressCandidate
            && ! $this->shouldOverrideQrAddress($qrAddressCandidate, $bestAddress)
        ) {
            $bestAddress = $qrAddressCandidate;
        }

        if ($bestAddress !== '') {
            $verification['address'] = $bestAddress;

            if ($qrProfile !== null && $qrAddressCandidate !== '' && $bestAddress !== $qrAddressCandidate) {
                $verification['qr_address_incomplete'] = true;
                $verification['_address_source'] = 'ocr_fallback';
            } elseif ($qrProfile !== null && $qrAddressCandidate !== '') {
                $verification['_address_source'] = 'qr';
            } elseif ($bestAddress === $ocrAddressCandidate) {
                $verification['_address_source'] = $verification['_address_source'] ?? 'ocr';
            } else {
                $verification['_address_source'] = $verification['_address_source'] ?? 'text_fallback';
            }
        }

        $qcidTempUpload = null;
        if ($verification['is_valid'] && $request->hasFile('qcid_image')) {
            try {
                $qcidTempUpload = $request->file('qcid_image')->store('qcid_scans_temp', 'public');
            } catch (\Throwable $e) {
                $qcidTempUpload = null;
            }
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
                'qcid_temp_upload' => $qcidTempUpload,
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'QC ID detected and verified successfully.',
            'verification' => $verification,
            'ocr_text' => $combinedText,
            'qr_id_number' => $qrIdNumber,
            'qcid_temp_upload' => $qcidTempUpload,
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
                            $resolved = (string) $pVal;
                            if ($targetKey === 'cardholder_name') {
                                $resolved = $this->normalizeEnyeCharacters($resolved);
                            } elseif ($targetKey === 'address') {
                                $resolved = $this->normalizeAddressText($resolved);
                            }
                            $profile[$targetKey] = $resolved;
                            $profile['_' . $targetKey . '_source'] = 'qr';
                            break;
                        }
                    }
                }

                // Build/augment address from discrete QR fields when available.
                $addrKeys = [
                    'house' => ['house_no', 'house_number', 'houseno', 'unit_no', 'unit_number', 'unit', 'door_no', 'street_no', 'number'],
                    'street' => ['street', 'st', 'st_name', 'street_name', 'road', 'road_name', 'avenue', 'ave', 'sitio', 'purok'],
                    'brgy' => ['barangay', 'brgy', 'brgy_name', 'bgy'],
                    'subd' => ['subdivision', 'subd', 'subdivision_name', 'village', 'vil', 'homes', 'phase'],
                    'block' => ['block', 'blk', 'blky'],
                    'lot' => ['lot', 'lt'],
                    'city' => ['city', 'city_name', 'municipality', 'munc'],
                    'province' => ['province', 'prov'],
                ];

                $assembled = [];
                foreach ($addrKeys as $label => $keys) {
                    foreach ($keys as $k) {
                        $val = $findKeyRecursively($data, $k);
                        if (!empty($val)) {
                            $assembled[$label] = trim((string) $val);
                            break;
                        }
                    }
                }

                $assembledAddress = $this->assembleAddressFromParts($assembled);
                if ($assembledAddress !== '') {
                    $bestAddress = $this->chooseBestAddress([
                        (string) ($profile['address'] ?? ''),
                        $assembledAddress,
                    ]);

                    if ($bestAddress !== '') {
                        $profile['address'] = $bestAddress;
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
                    $profile['cardholder_name'] = $this->normalizeEnyeCharacters(trim($parts[1]));
                    $profile['_id_number_source'] = 'qr';
                    $profile['_cardholder_name_source'] = 'qr';
                    if (isset($parts[2])) {
                        $profile['address'] = $this->normalizeAddressText(trim($parts[2]));
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
                                $resolved = (string) $v;
                                if ($targetKey === 'cardholder_name') {
                                    $resolved = $this->normalizeEnyeCharacters($resolved);
                                } elseif ($targetKey === 'address') {
                                    $resolved = $this->normalizeAddressText($resolved);
                                }
                                $profile[$targetKey] = $resolved;
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

    private function normalizeEnyeCharacters(string $value): string
    {
        $value = str_replace(['Ã‘', 'Ã±'], ['Ñ', 'ñ'], $value);
        $value = preg_replace('/N\x{0303}/u', 'Ñ', $value) ?? $value;
        $value = preg_replace('/n\x{0303}/u', 'ñ', $value) ?? $value;
        $value = preg_replace('/N\s*[~`´^¨]\s*(?=[AEIOU])/u', 'Ñ', $value) ?? $value;
        $value = preg_replace('/n\s*[~`´^¨]\s*(?=[aeiou])/u', 'ñ', $value) ?? $value;
        $value = preg_replace('/N\s*[~`´^¨]\s*(?=\b)/u', 'Ñ', $value) ?? $value;
        $value = preg_replace('/n\s*[~`´^¨]\s*(?=\b)/u', 'ñ', $value) ?? $value;

        return trim($value);
    }

    private function chooseBestCardholderName(array $candidates): string
    {
        $best = '';
        $bestScore = PHP_INT_MIN;

        foreach ($candidates as $candidate) {
            $name = trim($this->applyLikelyEnyeCorrections($this->normalizeEnyeCharacters((string) $candidate)));
            if ($name === '') {
                continue;
            }

            $score = 0;
            $score += min(70, mb_strlen($name));
            $score += substr_count($name, ',') > 0 ? 20 : 0;
            $score += mb_stripos($name, 'Ñ') !== false ? 45 : 0;
            $score -= preg_match('/\d/', $name) === 1 ? 30 : 0;

            if ($score > $bestScore) {
                $best = $name;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function applyLikelyEnyeCorrections(string $name): string
    {
        $name = $this->normalizeEnyeCharacters($name);

        // If Ñ is already present, do not force replacements.
        if (mb_stripos($name, 'Ñ') !== false) {
            return $name;
        }

        $replacements = [
            'MASCARINAS' => 'MASCARIÑAS',
            'CANETE' => 'CAÑETE',
            'MUNOZ' => 'MUÑOZ',
            'NINO' => 'NIÑO',
            'PENA' => 'PEÑA',
            'MANALAC' => 'MAÑALAC',
            'PINON' => 'PIÑON',
            'BANEZ' => 'BAÑEZ',
            'PANO' => 'PAÑO',
            'BANO' => 'BAÑO',
            'ANO' => 'AÑO',
        ];

        foreach ($replacements as $plain => $enye) {
            $pattern = '/\b' . preg_quote($plain, '/') . '\b/u';
            $name = preg_replace_callback($pattern . 'i', function (array $matches) use ($enye): string {
                $matched = (string) ($matches[0] ?? '');

                if ($matched === mb_strtoupper($matched, 'UTF-8')) {
                    return $enye;
                }

                if ($matched === mb_strtolower($matched, 'UTF-8')) {
                    return mb_strtolower($enye, 'UTF-8');
                }

                return mb_convert_case(mb_strtolower($enye, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
            }, $name) ?? $name;
        }

        return $name;
    }

    private function normalizeAddressText(string $value): string
    {
        $value = $this->normalizeEnyeCharacters($value);
        $value = str_replace(["\r\n", "\r", "\n"], ' ', $value);
        $value = preg_replace('/[^\p{L}0-9,\.\-#\/\s]/u', ' ', $value) ?? $value;

        // Remove known non-address labels that leak from OCR text blocks.
        $value = preg_replace('/\b(?:ADDRESS|CARDHOLDER|SIGNATURE|EMERGENCY|CONTACT|RELAY|DATE\s*ISSUED|VALID\s*UNTIL|DATE\s*(?:OF)?\s*BIRTH|CIVIL\s*STATUS|LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|SEX|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|Q\s*CITIZEN\s*CARD|CITIZEN\s*CARD|CITIZENCARD|QCITIZENCARD|QC\s*ID|KASAMA\s*KA\s*SA\s*PAG\s*-?\s*UNLAD|BLOOD\s*TYPE|TYPE\s*[ABO][\+\-]?|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED)\b/i', ' ', $value) ?? $value;

        // Cut trailing text once label-like words appear after the address.
        $value = preg_replace('/(?:,|\s)\b(?:CARDHOLDER|SIGNATURE|EMERGENCY|CONTACT|BLOOD\s*TYPE|TYPE\s*[ABO][\+\-]?)\b.*$/i', '', $value) ?? $value;

        // Remove obvious date fragments that should never be part of addresses.
        $value = preg_replace('/\b\d{4}[\/-]\d{1,2}[\/-]\d{1,2}\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\b\d{1,2}[\/-]\d{1,2}[\/-]\d{4}\b/', ' ', $value) ?? $value;

        // Fix common OCR city variants.
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s*,\s*/', ', ', $value) ?? $value;
        $value = preg_replace('/,{2,}/', ',', $value) ?? $value;
        $value = preg_replace('/\bQUEZON\s*(?:C1TY|CTY|1TY|ITY|LITY)\b/i', 'QUEZON CITY', $value) ?? $value;

        // Remove likely stray name token before barangay anchors (e.g. "EXT JEAN KINGSPOINT").
        $value = preg_replace_callback(
            '/\b(EXT|EXTENSION|ST|STREET|ROAD|RD|AVE|AVENUE|DR|DRIVE)\s+([A-Z]{3,12})\s+(KINGSPOINT|BAGBAG|NOVALICHES|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|SAN\s*BARTOLOME|TALIPAPA|PAYATAS|CUBAO|PROJECT\s*[4678]|MATANDANG\s*BALARA|PASONG\s*TAMO|PASONG\s*PUTIK|HOLY\s*SPIRIT|TANDANG\s*SORA|BAESA)\b/i',
            static function (array $m): string {
                $middle = strtoupper((string) ($m[2] ?? ''));
                $allowed = ['NORTH', 'SOUTH', 'EAST', 'WEST', 'NEW', 'OLD'];
                if (in_array($middle, $allowed, true)) {
                    return (string) $m[0];
                }

                return trim((string) ($m[1] ?? '')) . ' ' . trim((string) ($m[3] ?? ''));
            },
            $value
        ) ?? $value;

        // Keep content only up to the first valid city suffix to avoid tail noise.
        if (preg_match('/^(.*?\bQUEZON\s*CITY\b).*/i', $value, $m) === 1) {
            $value = (string) $m[1];
        }

        $value = $this->trimToAddressCore($value);

        $value = preg_replace('/\s*,\s*/', ', ', $value) ?? $value;
        $value = preg_replace('/,{2,}/', ',', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value, ' ,');
    }

    private function trimToAddressCore(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $segments = array_values(array_filter(array_map('trim', explode(',', $value))));
        if ($segments === []) {
            return $value;
        }

        $locationPattern = '/\b(?:#?\d{1,4}|BLK|BLOCK|LOT|UNIT|BRGY|BARANGAY|SUBD|SUBDIVISION|ST(?:REET)?|ROAD|RD|AVE(?:NUE)?|EXT(?:ENSION)?|PUROK|SITIO|VILLAGE|PHASE|BAESA|BAGBAG|NOVALICHES|KINGSPOINT|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|TALIPAPA|PAYATAS|CUBAO|HOLY\s*SPIRIT|TANDANG\s*SORA|SAN\s*BARTOLOME|PASONG\s*TAMO|PASONG\s*PUTIK|PROJECT\s*[0-9]+)\b/i';
        $noisePattern = '/\b(?:BLOOD|TYPE|SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED|CARDHOLDER|CITIZEN|QCID|NAME|SEX|STATUS)\b/i';

        $kept = [];
        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $hasLocationMarker = preg_match($locationPattern, $segment) === 1 || preg_match('/\bQUEZON\s*CITY\b/i', $segment) === 1;
            if (preg_match($noisePattern, $segment) === 1 && ! $hasLocationMarker) {
                continue;
            }

            if ($hasLocationMarker) {
                $kept[] = trim($segment, ' .');
            }
        }

        if ($kept === []) {
            return $value;
        }

        $result = implode(', ', $kept);
        if (preg_match('/\bQUEZON\s*CITY\b/i', $result) !== 1) {
            $result = trim($result, ' ,') . ', QUEZON CITY';
        }

        return trim($result, ' ,');
    }

    private function assembleAddressFromParts(array $parts): string
    {
        $block = trim((string) ($parts['block'] ?? ''));
        $lot = trim((string) ($parts['lot'] ?? ''));
        $house = trim((string) ($parts['house'] ?? ''));
        $street = trim((string) ($parts['street'] ?? ''));
        $subd = trim((string) ($parts['subd'] ?? ''));
        $brgy = trim((string) ($parts['brgy'] ?? ''));
        $city = trim((string) ($parts['city'] ?? ''));
        $province = trim((string) ($parts['province'] ?? ''));

        if ($block !== '' && preg_match('/\bBLK\b/i', $block) !== 1) {
            $block = 'BLK ' . $block;
        }
        if ($lot !== '' && preg_match('/\bLOT\b/i', $lot) !== 1) {
            $lot = 'LOT ' . $lot;
        }
        if ($house !== '' && preg_match('/^[0-9A-Z\-]+$/i', $house) === 1 && !str_starts_with($house, '#')) {
            $house = '#' . $house;
        }

        $line1 = trim(implode(' ', array_filter([$block, $lot, $house, $street])));
        $line2 = trim(implode(', ', array_filter([$subd, $brgy])));
        $location = trim(implode(', ', array_filter([$city, $province])));

        return $this->normalizeAddressText(trim(implode(', ', array_filter([$line1, $line2, $location]))));
    }

    private function extractAddressFromRawText(string $rawText): string
    {
        $normalized = $this->normalizeAddressText($rawText);
        if ($normalized === '') {
            return '';
        }

        if (preg_match('/((?:BLK|BLOCK|LOT|UNIT|#|\d{1,5})[A-Z0-9#,\.\-\s]{8,}?QUEZON\s*(?:CITY|C1TY|CTY|1TY|ITY))/i', $normalized, $m) === 1) {
            $candidate = $this->normalizeAddressText($m[1]);
            if ($this->looksPlausibleAddressCandidate($candidate)) {
                return $candidate;
            }
        }

        if (preg_match('/([A-Z0-9#,\.\-\s]{14,}?QUEZON\s*(?:CITY|C1TY|CTY|1TY|ITY))/i', $normalized, $m) === 1) {
            $candidate = $this->normalizeAddressText($m[1]);
            if ($this->looksPlausibleAddressCandidate($candidate)) {
                return $candidate;
            }
        }

        return '';
    }

    private function chooseBestAddress(array $candidates): string
    {
        $normalized = [];
        $plausible = [];
        foreach ($candidates as $candidate) {
            $value = $this->normalizeAddressText((string) $candidate);
            if ($value === '' || in_array($value, $normalized, true)) {
                continue;
            }
            $normalized[] = $value;

            if ($this->looksPlausibleAddressCandidate($value)) {
                $plausible[] = $value;
            }
        }

        if ($normalized === []) {
            return '';
        }

        $pool = $plausible !== [] ? $plausible : $normalized;

        $best = $pool[0];
        $bestScore = $this->addressQualityScore($best);

        foreach (array_slice($pool, 1) as $candidate) {
            $score = $this->addressQualityScore($candidate);
            if ($score > $bestScore) {
                $best = $candidate;
                $bestScore = $score;
            }
        }

        return $best;
    }

    private function shouldOverrideQrAddress(string $qrAddress, string $candidate): bool
    {
        $qr = $this->normalizeAddressText($qrAddress);
        $fallback = $this->normalizeAddressText($candidate);

        if ($qr === '' || $fallback === '' || $fallback === $qr) {
            return false;
        }

        if (! $this->looksPlausibleAddressCandidate($fallback) || $this->containsAddressNoise($fallback)) {
            return false;
        }

        // Avoid replacing QR with tiny/noisy differences.
        if (mb_strlen($fallback) < mb_strlen($qr) + 12) {
            return false;
        }

        $markers = ['BARANGAY', 'BRGY', 'SUBD', 'SUBDIVISION', 'VILLAGE', 'PHASE', 'BLK', 'BLOCK', 'LOT', 'UNIT', 'PUROK', 'SITIO'];
        foreach ($markers as $marker) {
            if (stripos($fallback, $marker) !== false && stripos($qr, $marker) === false) {
                return true;
            }
        }

        return false;
    }

    private function looksPlausibleAddressCandidate(string $address): bool
    {
        if ($address === '') {
            return false;
        }

        if (preg_match('/\bQUEZON\s*CITY\b/i', $address) !== 1) {
            return false;
        }

        if ($this->containsAddressNoise($address)) {
            return false;
        }

        return mb_strlen($address) >= 14;
    }

    private function containsAddressNoise(string $address): bool
    {
        return preg_match('/\b(?:CARDHOLDER|SIGNATURE|LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|DATE\s*ISSUED|VALID\s*UNTIL|DATE\s*(?:OF)?\s*BIRTH|CIVIL\s*STATUS|SEX|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|CITIZEN\s*CARD|CITIZENCARD|BLOOD\s*TYPE|TYPE\s*[ABO][\+\-]?)\b/i', $address) === 1;
    }

    private function addressQualityScore(string $address): int
    {
        $score = min(80, mb_strlen($address));
        $score += substr_count($address, ',') * 8;

        $patterns = [
            '/\bQUEZON\s*CITY\b/i' => 20,
            '/\b(?:BARANGAY|BRGY)\b/i' => 12,
            '/\b(?:SUBD|SUBDIVISION|VILLAGE|HOMES|PHASE)\b/i' => 12,
            '/\b(?:ST\.?|STREET|AVE\.?|AVENUE|ROAD|RD\.?|DRIVE|DR\.?)\b/i' => 10,
            '/\b(?:BLK|BLOCK|LOT|UNIT|#\d)\b/i' => 10,
        ];

        foreach ($patterns as $pattern => $points) {
            if (preg_match($pattern, $address) === 1) {
                $score += $points;
            }
        }

        if ($this->containsAddressNoise($address)) {
            $score -= 140;
        }

        return $score;
    }
}
