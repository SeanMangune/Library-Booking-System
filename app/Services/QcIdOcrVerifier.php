<?php

namespace App\Services;

class QcIdOcrVerifier
{
    /**
     * Verify OCR text against the expected Quezon City Citizen ID layout.
     *
     * Uses a weighted signal-scoring approach that is tolerant of noisy,
     * real-world OCR output from phone-camera photos of physical cards.
     *
     * @return array<string, mixed>
     */
    public function verify(string $rawText, ?string $enteredName = null): array
    {
        $normalized = $this->normalizeText($rawText);
        $lines = $this->extractLines($rawText);
        $matchedMarkers = $this->matchedMarkers($normalized);
        $fragmentHits = $this->detectFragments($normalized);
        $fields = $this->extractFields($normalized, $lines);

        // ── weighted score ──────────────────────────────────────────────
        $score = 0;

        // Full marker matches (high confidence)
        $markerKeys = array_column($matchedMarkers, 'key');
        foreach ($markerKeys as $key) {
            $score += match ($key) {
                'qc_citizen_card' => 30,
                'quezon_city' => 20,
                'kasama_pag_unlad' => 25,
                'republic_of_the_philippines' => 15,
                'cardholder_signature' => 15,
                'date_issued' => 10,
                'valid_until' => 10,
                default => 5,
            };
        }

        // Fragment / fuzzy hits (partial OCR matches)
        foreach ($fragmentHits as $frag) {
            $score += match ($frag) {
                'citizen_fragment' => 18,
                'quezon_fragment' => 15,
                'kasama_fragment' => 15,
                'republic_fragment' => 10,
                'philippines_fragment' => 10,
                'cardholder_fragment' => 8,
                'pag_unlad_fragment' => 12,
                default => 5,
            };
        }

        // Field-label detection (even if the value was not parsed, the
        // label text itself indicates a QC ID card layout)
        $labelScore = $this->detectFieldLabels($normalized);
        $score += $labelScore;

        // Parsed-field bonuses
        $parsedFieldCount = count(array_filter([
            $fields['cardholder_name'] ?? null,
            $fields['sex'] ?? null,
            $fields['blood_type'] ?? null,
            $fields['date_of_birth'] ?? null,
            $fields['civil_status'] ?? null,
            $fields['date_issued'] ?? null,
            $fields['valid_until'] ?? null,
            $fields['address'] ?? null,
            $fields['id_number'] ?? null,
        ]));

        $score += $parsedFieldCount * 5;

        // Any date-like string (YYYY/MM/DD or DD/MM/YYYY etc.) in the
        // full normalized text adds a small bonus regardless of labels.
        if (preg_match('/\d{4}[\/-]\d{2}[\/-]\d{2}|\d{2}[\/-]\d{2}[\/-]\d{4}/', $normalized)) {
            $score += 5;
        }

        // Long digit sequence typical of QC ID numbers
        if (preg_match('/\d{3}\s*\d{3}\s*\d{5,8}|\d{10,14}/', $normalized)) {
            $score += 8;
        }

        // ── decision ────────────────────────────────────────────────────
        // Threshold: 40 points is enough – a single "CITIZENCARD" marker
        // (30) plus any two fragment hits or a couple of parsed fields
        // will cross this bar.
        $looksLikeQcId = $score >= 40;

        // Optional name-match gate – only applied when a name was
        // entered AND the card name was successfully parsed.
        $nameMatches = null;
        if ($enteredName !== null && trim($enteredName) !== '' && ! empty($fields['cardholder_name'])) {
            $nameMatches = $this->namesLikelyMatch($enteredName, $fields['cardholder_name']);
            // Name mismatch only downgrades – never outright rejects –
            // because OCR frequently garbles names.
            if (! $nameMatches) {
                $score -= 15;
                $looksLikeQcId = $score >= 40;
            }
        }

        $confidenceScore = min(100, $score);

        // ── non-QC ID rejection ──────────────────────────────────────
        // Always check for non-QC IDs. If detected AND no strong
        // QC-specific marker is present, force rejection.
        $rejectedIdType = $this->detectNonQcId($normalized);
        $hasStrongQcMarker = in_array('qc_citizen_card', $markerKeys)
            || in_array('kasama_pag_unlad', $markerKeys);
        if ($rejectedIdType !== null) {
            if (! $hasStrongQcMarker) {
                $looksLikeQcId = false;
                $confidenceScore = 0;
            } else {
                // Strong QC markers present – false positive, ignore
                // Keep the non-QC signal for fake QC ID checks.
            }
        }

        $idAssessment = 'INVALID';
        $fakeReason = null;
        if ($looksLikeQcId) {
            $fakeReason = $this->detectFakeQcId($normalized, $fields, $matchedMarkers, $rejectedIdType);
            $idAssessment = $fakeReason !== null ? 'Fake QC ID' : 'Verified';
        }

        return [
            'is_valid' => $idAssessment === 'Verified',
            'confidence_score' => $confidenceScore,
            'matched_markers' => array_column($matchedMarkers, 'label'),
            'marker_count' => count($matchedMarkers),
            'cardholder_name' => $fields['cardholder_name'] ?? null,
            'sex' => $fields['sex'] ?? null,
            'blood_type' => $fields['blood_type'] ?? null,
            'date_of_birth' => $fields['date_of_birth'] ?? null,
            'civil_status' => $fields['civil_status'] ?? null,
            'date_issued' => $fields['date_issued'] ?? null,
            'valid_until' => $fields['valid_until'] ?? null,
            'address' => $fields['address'] ?? null,
            'id_number' => $fields['id_number'] ?? null,
            'name_matches' => $nameMatches,
            'rejected_id_type' => $rejectedIdType,
            'id_assessment' => $idAssessment,
            'fake_reason' => $fakeReason,
            'normalized_text' => $normalized,
        ];
    }

    /**
     * @param  list<array{key: string, label: string}>  $matchedMarkers
     * @param  array<string, string|null>  $fields
     */
    private function detectFakeQcId(
        string $normalized,
        array $fields,
        array $matchedMarkers,
        ?string $rejectedIdType
    ): ?string {
        if (preg_match('/\b(FAKE|SAMPLE|SPECIMEN|NOT\s+VALID|FOR\s+DISPLAY\s+ONLY|DEMO|MOCK\s*UP|DUMMY|PROTOTYPE|VOID|TESTING\s*ONLY|FOR\s*SYSTEM\s*TESTING|MOCK)\b/i', $normalized) === 1) {
            return 'Image text contains fake/sample markers.';
        }

        $markerKeys = array_column($matchedMarkers, 'key');
        $hasStrongQcMarker = in_array('qc_citizen_card', $markerKeys, true)
            || in_array('kasama_pag_unlad', $markerKeys, true);

        if ($hasStrongQcMarker && $rejectedIdType !== null) {
            return "QC markers exist but non-QC ID markers were also detected ({$rejectedIdType}).";
        }

        $idNumber = $fields['id_number'] ?? null;
        if ($idNumber !== null) {
            $digits = preg_replace('/\D/', '', $idNumber) ?? '';
            // All-same digits (e.g. 11111111111111)
            if ($digits !== '' && preg_match('/^(\d)\1{9,}$/', $digits) === 1) {
                return 'QC ID number pattern is suspicious (repeating digits).';
            }
            // Sequential digits (12345678901234)
            if ($digits !== '' && preg_match('/^(?:0123456789|1234567890|9876543210)/', $digits) === 1) {
                return 'QC ID number pattern is suspicious (sequential digits).';
            }
            // Too short or too long for a real QC ID number
            if (strlen($digits) > 0 && (strlen($digits) < 8 || strlen($digits) > 16)) {
                return 'QC ID number length is inconsistent with genuine QC IDs.';
            }
        }

        // Known sample QC ID numbers used in training/testing
        $knownFakeIds = ['00000000000000', '12345678901234', '99999999999999', '11111111111111', '9990009876543210'];
        if ($idNumber !== null) {
            $cleanId = preg_replace('/[\s\-]/', '', $idNumber) ?? '';
            if (in_array($cleanId, $knownFakeIds, true)) {
                return 'This QC ID number matches a known sample/test ID.';
            }
        }

        // Check for known sample names commonly used in fake IDs
        $cardholderName = strtoupper($fields['cardholder_name'] ?? '');
        $sampleNames = ['JUAN DELA CRUZ', 'JANE DOE', 'JOHN DOE', 'MARIA CLARA', 'JOSE RIZAL', 'TEST USER', 'SAMPLE NAME'];
        foreach ($sampleNames as $sampleName) {
            if ($cardholderName !== '' && str_contains($cardholderName, $sampleName)) {
                return 'Cardholder name matches a known sample/placeholder name.';
            }
        }

        return null;
    }

    // ────────────────────────────────────────────────────────────────────
    // Fragment / fuzzy detection
    // ────────────────────────────────────────────────────────────────────

    /**
     * Detect partial / garbled fragments of QC ID keywords.
     *
     * OCR on coloured card backgrounds frequently produces partial
     * matches like "ITIZEN", "QUEZO", "ASAMA", etc.
     *
     * @return list<string>
     */
    private function detectFragments(string $normalized): array
    {
        $fragments = [];

        // Citizen / CitizenCard fragments
        $citizenPatterns = [
            '/C\s*I\s*T\s*I\s*Z\s*E\s*N/',  // letters possibly spaced
            '/[CG][I1]T[I1]ZEN/',             // OCR substitutions
            '/TIZEN\s*CARD/',
            '/CITI\s*ZEN/',
            '/CITIZ/',
            '/IZEN\s*CARD/',
        ];
        foreach ($citizenPatterns as $p) {
            if (preg_match($p, $normalized)) {
                $fragments[] = 'citizen_fragment';
                break;
            }
        }

        // Quezon fragments
        $quezonPatterns = [
            '/QU?E\s*Z\s*O\s*N/',
            '/QUEZO[MN]/',
            '/Q\s*CITY/',
            '/QUEZON/',
        ];
        foreach ($quezonPatterns as $p) {
            if (preg_match($p, $normalized)) {
                $fragments[] = 'quezon_fragment';
                break;
            }
        }

        // Kasama fragments
        if (preg_match('/KASAMA|ASAMA\s+KA|KA\s+SA\s+PAG/', $normalized)) {
            $fragments[] = 'kasama_fragment';
        }

        // Pag-unlad fragments
        if (preg_match('/PAG\s*-?\s*UNLAD|UNLAD/', $normalized)) {
            $fragments[] = 'pag_unlad_fragment';
        }

        // Republic / Philippines fragments
        if (preg_match('/REPUB\w*|R\s*E\s*P\s*U\s*B\s*L\s*I\s*C/', $normalized)) {
            $fragments[] = 'republic_fragment';
        }

        if (preg_match('/PHILIP\w*|PILIP\w*|PHIL\s*I\s*P/', $normalized)) {
            $fragments[] = 'philippines_fragment';
        }

        // Cardholder fragments
        if (preg_match('/CARDHO\w*|CARD\s*HOLDER/', $normalized)) {
            $fragments[] = 'cardholder_fragment';
        }

        return $fragments;
    }

    /**
     * Detect field-label text that typically appears on a QC ID.
     *
     * Even if the actual value could not be parsed, the presence of
     * these label strings is evidence of a QC ID layout.
     */
    private function detectFieldLabels(string $normalized): int
    {
        $labels = [
            '/LAST\s*NAME/' => 4,
            '/FIRST\s*NAME/' => 4,
            '/MIDDLE\s*NAME/' => 3,
            '/DATE\s*(OF)?\s*BIRTH/' => 4,
            '/SEX/' => 2,
            '/CIVIL\s*STATUS/' => 4,
            '/DATE\s*ISSUED/' => 4,
            '/VALID\s*UNTIL/' => 4,
            '/SIGNATURE/' => 3,
            '/IN\s*CASE\s*OF\s*EMERGENCY/' => 5,
            '/BARANGAY|BRGY/' => 3,
            '/CONSTITUENCY/' => 4,
        ];

        $total = 0;
        foreach ($labels as $pattern => $points) {
            if (preg_match($pattern, $normalized)) {
                $total += $points;
            }
        }

        return $total;
    }

    // ────────────────────────────────────────────────────────────────────
    // Name comparison
    // ────────────────────────────────────────────────────────────────────

    public function namesLikelyMatch(string $enteredName, string $cardholderName): bool
    {
        $enteredTokens = $this->nameTokens($enteredName);
        $cardholderTokens = $this->nameTokens($cardholderName);

        if ($enteredTokens === [] || $cardholderTokens === []) {
            return false;
        }

        // Check exact token overlap first
        $overlap = array_values(array_intersect($enteredTokens, $cardholderTokens));
        $required = min(count($enteredTokens), count($cardholderTokens));

        if ($required <= 2) {
            if (count($overlap) === $required) {
                return true;
            }
        } elseif (count($overlap) >= 2) {
            return true;
        }

        // Fuzzy fallback – check if any entered token is a substring of
        // any card token or vice-versa (handles OCR truncation / extras).
        $fuzzyHits = 0;
        foreach ($enteredTokens as $et) {
            foreach ($cardholderTokens as $ct) {
                if (strlen($et) >= 3 && strlen($ct) >= 3) {
                    if (str_contains($ct, $et) || str_contains($et, $ct)) {
                        $fuzzyHits++;
                        break;
                    }
                    // Allow 1-char edit distance for short tokens
                    if (levenshtein($et, $ct) <= 1) {
                        $fuzzyHits++;
                        break;
                    }
                }
            }
        }

        return $fuzzyHits >= min(2, $required);
    }

    // ────────────────────────────────────────────────────────────────────
    // Text normalisation helpers
    // ────────────────────────────────────────────────────────────────────

    private function normalizeText(string $text): string
    {
        $text = mb_strtoupper($text, 'UTF-8');
        $text = str_replace(["\r\n", "\r", "\n"], ' ', $text);
        $text = preg_replace('/[^A-Z0-9\/,&.\-\+\s]/u', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }

    /**
     * @return list<string>
     */
    private function extractLines(string $text): array
    {
        $lines = preg_split('/\R+/', mb_strtoupper($text, 'UTF-8')) ?: [];

        $cleaned = array_map(function (string $line): string {
            $line = preg_replace('/[^A-Z0-9\/,&.\-\+\s]/u', ' ', $line) ?? $line;
            $line = preg_replace('/\s+/', ' ', $line) ?? $line;

            return trim($line);
        }, $lines);

        return array_values(array_filter($cleaned, fn (string $line) => $line !== ''));
    }

    // ────────────────────────────────────────────────────────────────────
    // Full marker matching (kept for backward compatibility and higher-
    // confidence hits)
    // ────────────────────────────────────────────────────────────────────

    /**
     * @return list<array{key: string, label: string}>
     */
    private function matchedMarkers(string $normalized): array
    {
        $definitions = [
            'qc_citizen_card' => [
                'label' => 'QC Citizen Card',
                'pattern' => '/Q\s*C?\s*CITIZEN\s*CARD|QCITIZENCARD|Q\s*CITIZENCARD|QC\s*CITIZEN\s*CARD|CITIZENCARD|QC\s*CARD/',
            ],
            'republic_of_the_philippines' => [
                'label' => 'Republic of the Philippines',
                'pattern' => '/REPUBLIC\s+OF\s+THE\s+PHILIPPINES/',
            ],
            'date_issued' => [
                'label' => 'Date Issued',
                'pattern' => '/DATE\s*ISSUED/',
            ],
            'valid_until' => [
                'label' => 'Valid Until',
                'pattern' => '/VALID\s*UNTIL/',
            ],
            'quezon_city' => [
                'label' => 'Quezon City',
                'pattern' => '/QUEZON\s*CITY/',
            ],
            'cardholder_signature' => [
                'label' => 'Cardholder Signature',
                'pattern' => '/CARD\s*HOLDER\s*S?\s*IGNATURE/',
            ],
            'kasama_pag_unlad' => [
                'label' => 'Kasama Ka Sa Pag-Unlad',
                'pattern' => '/KASAMA\s+KA\s+SA\s+PAG\s*-?\s*UNLAD/',
            ],
        ];

        $matches = [];

        foreach ($definitions as $key => $definition) {
            if (preg_match($definition['pattern'], $normalized) === 1) {
                $matches[] = [
                    'key' => $key,
                    'label' => $definition['label'],
                ];
            }
        }

        return $matches;
    }

    // ────────────────────────────────────────────────────────────────────
    // Field extraction
    // ────────────────────────────────────────────────────────────────────

    /**
     * @param  list<string>  $lines
     * @return array<string, string|null>
     */
    private function extractFields(string $normalized, array $lines): array
    {
        $fields = [
            'cardholder_name' => $this->extractCardholderName($normalized, $lines),
            'sex' => ($layoutFields['sex'] ?? null) ?? $this->extractSex($normalized),
            'blood_type' => ($layoutFields['blood_type'] ?? null) ?? $this->extractBloodType($normalized),
            'date_of_birth' => ($layoutFields['date_of_birth'] ?? null) ?? $this->extractDate($normalized, $lines),
            'civil_status' => ($layoutFields['civil_status'] ?? null) ?? $this->extractCivilStatus($normalized),
            'date_issued' => ($layoutFields['date_issued'] ?? null) ?? $this->extractDate($normalized, $lines),
            'valid_until' => ($layoutFields['valid_until'] ?? null) ?? $this->extractDate($normalized, $lines),
            'id_number' => $this->extractIdNumber($normalized, $lines),
        ];

        foreach (['date_of_birth', 'date_issued', 'valid_until'] as $dateKey) {
            if (! empty($fields[$dateKey])) {
                $fields[$dateKey] = $this->normalizeDateToYmd((string) $fields[$dateKey]);
            }
        }

        // Now extract address with known date context for cleaning
        $fields['address'] = $this->extractAddress($lines, $normalized, [
            $fields['date_of_birth'],
            $fields['date_issued'],
            $fields['valid_until'],
        ]);

        // Positional fallback: if dates are still missing, collect all
        // date-like strings in the text and assign using year heuristics.
        // DOB year: 1940-2015, date_issued year: recent past, valid_until: future.
        $missingDates = empty($fields['date_of_birth'])
            || empty($fields['date_issued'])
            || empty($fields['valid_until']);

        if ($missingDates) {
            $allDates = $this->collectAllDates($normalized);
            $currentYear = (int) date('Y');

            // Remove dates already assigned
            $assignedDates = array_filter([
                $fields['date_of_birth'] ?? null,
                $fields['date_issued'] ?? null,
                $fields['valid_until'] ?? null,
            ]);
            $remainingDates = array_values(array_diff($allDates, $assignedDates));

            // Try year-based smart assignment for remaining dates
            foreach ($remainingDates as $date) {
                $year = (int) substr($date, 0, 4);

                if (empty($fields['date_of_birth']) && $year >= 1940 && $year <= 2015) {
                    $fields['date_of_birth'] = $date;
                } elseif (empty($fields['valid_until']) && $year > $currentYear) {
                    $fields['valid_until'] = $date;
                } elseif (empty($fields['date_issued']) && $year >= 2015 && $year <= $currentYear) {
                    $fields['date_issued'] = $date;
                }
            }

            // Still missing? fall back to positional order
            if (empty($fields['date_of_birth']) && isset($allDates[0])) {
                $fields['date_of_birth'] = $allDates[0];
            }
            if (empty($fields['date_issued']) && isset($allDates[1])) {
                $fields['date_issued'] = $allDates[1];
            }
            if (empty($fields['valid_until']) && isset($allDates[2])) {
                $fields['valid_until'] = $allDates[2];
            }

            // Label-context fallback: search around DATE ISSUED / VALID UNTIL lines.
            if (empty($fields['date_issued']) || empty($fields['valid_until'])) {
                $labelContextDates = $this->extractIssuedValidityByLabelContext($lines);
                if (empty($fields['date_issued']) && ! empty($labelContextDates['date_issued'])) {
                    $fields['date_issued'] = $labelContextDates['date_issued'];
                }
                if (empty($fields['valid_until']) && ! empty($labelContextDates['valid_until'])) {
                    $fields['valid_until'] = $labelContextDates['valid_until'];
                }
            }

            if (empty($fields['date_issued'])) {
                $fields['date_issued'] = $this->extractNearestDateToLabel($normalized, 'DATE\s*ISSUED');
            }
            if (empty($fields['valid_until'])) {
                $fields['valid_until'] = $this->extractNearestDateToLabel($normalized, 'VALID\s*UNTIL');
            }

            if (empty($fields['date_issued']) && ! empty($fields['valid_until'])) {
                $issuedCandidate = $this->bestIssueDateCandidate($allDates, $fields['date_of_birth'] ?? null, $fields['valid_until']);
                if ($issuedCandidate !== null) {
                    $fields['date_issued'] = $issuedCandidate;
                }
            }
        }

        // ── Date sanity ─────────────────────────────────────────────
        // If valid_until equals date_of_birth, the OCR likely garbled
        // the validity year (e.g. 2034 → 2003).  Clear it rather than
        // show a clearly wrong duplicate.
        if (! empty($fields['valid_until']) && ! empty($fields['date_of_birth'])
            && $fields['valid_until'] === $fields['date_of_birth']) {
            $fields['valid_until'] = null;
        }
        if (! empty($fields['date_issued']) && ! empty($fields['date_of_birth'])
            && $fields['date_issued'] === $fields['date_of_birth']) {
            $fields['date_issued'] = null;
        }

        $currentYear = (int) date('Y');

        // If validity is parsed as a past/current date and date_issued is empty,
        // it is usually the issue date mis-assigned into valid_until.
        if (! empty($fields['valid_until']) && empty($fields['date_issued'])) {
            $validYear = (int) substr((string) $fields['valid_until'], 0, 4);
            if ($validYear <= $currentYear) {
                $fields['date_issued'] = $fields['valid_until'];
                $fields['valid_until'] = null;
            }
        }

        // If both are present but only one is in the future, keep future as valid_until.
        if (! empty($fields['date_issued']) && ! empty($fields['valid_until'])) {
            $issuedYear = (int) substr((string) $fields['date_issued'], 0, 4);
            $validYear = (int) substr((string) $fields['valid_until'], 0, 4);

            if ($issuedYear > $currentYear && $validYear <= $currentYear) {
                [$fields['date_issued'], $fields['valid_until']] = [$fields['valid_until'], $fields['date_issued']];
            }

            if (strcmp((string) $fields['date_issued'], (string) $fields['valid_until']) > 0) {
                [$fields['date_issued'], $fields['valid_until']] = [$fields['valid_until'], $fields['date_issued']];
            }
        }

        // Year-range heuristic: swap mis-assigned dates.
        // DOB year is typically 1940-2015; valid_until year > current year.
        foreach (['date_issued', 'valid_until'] as $key) {
            if (! empty($fields[$key]) && ! empty($fields['date_of_birth'])) {
                $otherYear = (int) substr($fields[$key], 0, 4);
                $dobYear   = (int) substr($fields['date_of_birth'], 0, 4);
                // A date with birth-like year sitting in valid_until,
                // while DOB holds a future year → swap.
                if ($dobYear > $currentYear && $otherYear >= 1940 && $otherYear <= 2015) {
                    [$fields['date_of_birth'], $fields[$key]] = [$fields[$key], $fields['date_of_birth']];
                }
            }
        }

        return $fields;
    }

    /**
     * Extract sex field – tolerate OCR noise near the label.
     */
    private function extractSex(string $normalized): ?string
    {
        // Direct match: SEX M or SEX F
        if (preg_match('/\bSEX\s*[:\s]*([MF])\b/', $normalized, $m)) {
            return $m[1];
        }

        // Nearby context: MALE / FEMALE close to SEX label
        if (preg_match('/\bSEX\b/', $normalized) && preg_match('/\b(MALE|FEMALE)\b/', $normalized, $m)) {
            return $m[1][0]; // first letter
        }

        // QC ID layout: M/F followed by date (actual card order: M 2003/01/01)
        if (preg_match('/\b([MF])\s+\d{2,4}[\/\-]\d{2}/', $normalized, $m)) {
            return $m[1];
        }

        // QC ID layout: M/F followed by civil status value (value before label)
        if (preg_match('/\b([MF])\s+(?:SINGLE|MARRIED|WIDOW(?:ED)?|SEPARATED|DIVORCED|ANNULLED)\b/', $normalized, $m)) {
            return $m[1];
        }

        return null;
    }

    private function extractBloodType(string $normalized): ?string
    {
        if (preg_match('/\bBLOOD\s*TYPE\s*[:\s]*([ABO0]{1,2}\s*[\+\-])\b/', $normalized, $m)) {
            return str_replace('0', 'O', strtoupper(preg_replace('/\s+/', '', $m[1]) ?? $m[1]));
        }

        if (preg_match('/\b([ABO0]{1,2}\s*[\+\-])\s+DATE\s*ISSUED\b/', $normalized, $m)) {
            return str_replace('0', 'O', strtoupper(preg_replace('/\s+/', '', $m[1]) ?? $m[1]));
        }

        return null;
    }



    /**
     * Collect all date-like strings from the normalized text in order of
     * appearance. Used as a positional fallback when label-based
     * extraction fails (common with noisy OCR).
     *
     * @return list<string>  Dates in YYYY/MM/DD format.
     */
    private function collectAllDates(string $normalized): array
    {
        $dates = [];
        $digitCorrected = $this->ocrToDigits($normalized);

        // Match dates with separators: YYYY/MM/DD
        if (preg_match_all('/\b(\d{4}[\/-]\d{2}[\/-]\d{2})\b/', $normalized, $m)) {
            foreach ($m[1] as $d) {
                $normalizedDate = $this->normalizeDateToYmd($d);
                if ($normalizedDate !== null) {
                    $dates[] = $normalizedDate;
                }
            }
        }
        if ($digitCorrected !== $normalized && preg_match_all('/\b(\d{4}[\/-]\d{2}[\/-]\d{2})\b/', $digitCorrected, $m)) {
            foreach ($m[1] as $d) {
                $normalizedDate = $this->normalizeDateToYmd($d);
                if ($normalizedDate !== null && ! in_array($normalizedDate, $dates, true)) {
                    $dates[] = $normalizedDate;
                }
            }
        }

        // Match dates that may be in MM/DD/YYYY or DD/MM/YYYY.
        if (preg_match_all('/\b(\d{2}[\/-]\d{2}[\/-]\d{4})\b/', $digitCorrected, $m)) {
            foreach ($m[1] as $d) {
                $normalizedDate = $this->normalizeDateToYmd($d);
                if ($normalizedDate !== null && ! in_array($normalizedDate, $dates, true)) {
                    $dates[] = $normalizedDate;
                }
            }
        }

        // Match 8-digit dates without separators (e.g. 20030101)
        if (preg_match_all('/\b(\d{8})\b/', $normalized, $m)) {
            foreach ($m[1] as $d) {
                $year = (int) substr($d, 0, 4);
                if ($year >= 1900 && $year <= 2099) {
                    $dates[] = substr($d, 0, 4) . '/' . substr($d, 4, 2) . '/' . substr($d, 6, 2);
                }
            }
        }

        // Match garbled dates (e.g. 20030/01, 2034/0101)
        if (preg_match_all('/\b(\d{4,5}[\/-]\d{2,4}(?:[\/-]\d{2})?)\b/', $normalized, $m)) {
            foreach ($m[1] as $d) {
                // Skip if we already matched this as a clean date
                if (in_array($d, $dates)) {
                    continue;
                }
                $parsed = $this->parseGarbledDate($d);
                if ($parsed !== null && ! in_array($parsed, $dates)) {
                    $dates[] = $parsed;
                }
            }
        }

        // Deduplicate while preserving order
        return array_values(array_unique($dates));
    }

    /**
     * Attempt to parse a garbled OCR date string into YYYY/MM/DD.
     *
     * Handles common Tesseract artefacts like:
     *   20030/01 → 2003/01/01  (extra digit merged)
     *   2003/0101 → 2003/01/01 (separator dropped)
     *   2003 01 01 → 2003/01/01 (spaces as separators)
     */
    private function parseGarbledDate(string $raw): ?string
    {
        // Strip everything except digits
        $digits = preg_replace('/[^\d]/', '', $raw);

        // 8 digits: clean YYYYMMDD
        if (strlen($digits) === 8) {
            $year = (int) substr($digits, 0, 4);
            if ($year >= 1900 && $year <= 2099) {
                return substr($digits, 0, 4) . '/' . substr($digits, 4, 2) . '/' . substr($digits, 6, 2);
            }
        }

        // 7 digits: likely a dropped/merged digit situation
        // Try: YYYY M DD (e.g. 2003 1 01 → 2003/01/01) or YYYY MM D
        if (strlen($digits) === 7) {
            $year = (int) substr($digits, 0, 4);
            if ($year >= 1900 && $year <= 2099) {
                $month = substr($digits, 4, 1);
                $day = substr($digits, 5, 2);
                if ((int) $month >= 1 && (int) $month <= 9 && (int) $day >= 1 && (int) $day <= 31) {
                    return substr($digits, 0, 4) . '/0' . $month . '/' . $day;
                }
                // Try: YYYY MM D
                $month = substr($digits, 4, 2);
                $day = substr($digits, 6, 1);
                if ((int) $month >= 1 && (int) $month <= 12 && (int) $day >= 1 && (int) $day <= 9) {
                    return substr($digits, 0, 4) . '/' . $month . '/0' . $day;
                }
            }
        }

        // 9 digits: likely extra digit added somewhere
        if (strlen($digits) === 9) {
            // Try dropping the 5th digit (most common OCR error in year/month boundary)
            $try = substr($digits, 0, 4) . substr($digits, 5);
            if (strlen($try) === 8) {
                $year = (int) substr($try, 0, 4);
                if ($year >= 1900 && $year <= 2099) {
                    return substr($try, 0, 4) . '/' . substr($try, 4, 2) . '/' . substr($try, 6, 2);
                }
            }
        }

        return null;
    }

    /**
     * Correct common OCR letter↔digit confusions.
     * QC ID numbers are ALL digits, so convert letters to their most
     * likely numeric equivalents.
     */
    private function ocrToDigits(string $raw): string
    {
        return strtr($raw, [
            'O' => '0', 'o' => '0',
            'D' => '0', 'Q' => '0',
            'I' => '1', 'l' => '1', 'L' => '1',
            'Z' => '2', 'z' => '2',
            'E' => '3',
            'A' => '4', 'a' => '4',
            'S' => '5', 's' => '5',
            'G' => '6', 'b' => '6',
            'T' => '7',
            'B' => '8',
            'P' => '0', 'p' => '0',
        ]);
    }

    private function formatIdNumber(string $value): ?string
    {
        $digits = preg_replace('/\D/', '', $this->ocrToDigits($value)) ?? '';

        if ($digits === '') {
            return null;
        }

        // QC ID format target: 3-3-8 digits. If OCR dropped one leading zero,
        // recover to 14 digits before formatting.
        if (strlen($digits) === 13) {
            $digits = '0' . $digits;
        }

        // Some OCR runs drop 2 leading zeros from the 8-digit tail.
        if (strlen($digits) === 12) {
            $digits = '00' . $digits;
        }

        // Some OCR runs drop one digit in the last block. Recover by
        // inserting a leading zero in the final segment: 3+3+7 -> 3+3+8.
        if (strlen($digits) === 13 && preg_match('/^\d{6}\d{7}$/', $digits) === 1) {
            $digits = substr($digits, 0, 6) . '0' . substr($digits, 6);
        }

        if (strlen($digits) !== 14) {
            return null;
        }

        return substr($digits, 0, 3) . ' ' . substr($digits, 3, 3) . ' ' . substr($digits, 6, 8);

    }

    /**
     * Normalize a date (MM/DD/YYYY or YYYY-MM-DD) into YYYY-MM-DD.
     */
    public function normalizeDateToYmd(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim(str_replace('-', '/', $this->ocrToDigits($value)));

        if (preg_match('/^(\d{4})\/(\d{2})\/(\d{2})$/', $value, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            $day = (int) $m[3];
            if ($year >= 1900 && $year <= 2099 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^(\d{4})\/(\d{4})$/', $value, $m)) {
            $year = (int) $m[1];
            $month = (int) substr($m[2], 0, 2);
            $day = (int) substr($m[2], 2, 2);
            if ($year >= 1900 && $year <= 2099 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^(\d{6})\/(\d{2})$/', $value, $m)) {
            $year = (int) substr($m[1], 0, 4);
            $month = (int) substr($m[1], 4, 2);
            $day = (int) $m[2];
            if ($year >= 1900 && $year <= 2099 && $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }
        }

        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $value, $m)) {
            $a = (int) $m[1];
            $b = (int) $m[2];
            $year = (int) $m[3];
            if ($year < 1900 || $year > 2099) {
                return null;
            }

            // If first token exceeds 12, interpret as DD/MM/YYYY; otherwise MM/DD/YYYY.
            $month = $a > 12 ? $b : $a;
            $day = $a > 12 ? $a : $b;

            if ($month >= 1 && $month <= 12 && $day >= 1 && $day <= 31) {
                return sprintf('%04d/%02d/%02d', $year, $month, $day);
            }
        }

        return null;
    }

    /**
     * @param list<string> $lines
     * @return array{date_issued: string|null, valid_until: string|null}
     */
    private function extractIssuedValidityByLabelContext(array $lines): array
    {
        $dateIssued = null;
        $validUntil = null;
        $currentYear = (int) date('Y');

        foreach ($lines as $index => $line) {
            if (! preg_match('/DATE\s*ISSUED|VALID\s*UNTIL/', $line)) {
                continue;
            }

            $scope = implode(' ', array_filter([
                $lines[$index - 1] ?? null,
                $line,
                $lines[$index + 1] ?? null,
            ]));

            $dates = $this->collectAllDates($scope);
            foreach ($dates as $date) {
                $normalizedDate = $this->normalizeDateToYmd($date);
                if ($normalizedDate === null) {
                    continue;
                }

                $year = (int) substr($normalizedDate, 0, 4);
                if ($dateIssued === null && $year >= 2015 && $year <= $currentYear) {
                    $dateIssued = $normalizedDate;
                    continue;
                }
                if ($validUntil === null && $year > $currentYear) {
                    $validUntil = $normalizedDate;
                }
            }
        }

        return [
            'date_issued' => $dateIssued,
            'valid_until' => $validUntil,
        ];
    }

    private function extractNearestDateToLabel(string $normalized, string $labelPattern): ?string
    {
        if (preg_match('/' . $labelPattern . '/i', $normalized, $m, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $labelOffset = (int) $m[0][1];
        $candidates = $this->collectDateCandidatesWithOffsets($normalized);
        if ($candidates === []) {
            return null;
        }

        $nearestDate = null;
        $nearestDistance = PHP_INT_MAX;

        foreach ($candidates as $candidate) {
            $distance = abs($candidate['offset'] - $labelOffset);
            if ($distance < $nearestDistance) {
                $nearestDistance = $distance;
                $nearestDate = $candidate['date'];
            }
        }

        return $nearestDate;
    }

    /**
     * @return list<array{date: string, offset: int}>
     */
    private function collectDateCandidatesWithOffsets(string $normalized): array
    {
        $candidates = [];
        $patterns = [
            '/\b\d{4}[\/-]\d{2}[\/-]\d{2}\b/',
            '/\b\d{2}[\/-]\d{2}[\/-]\d{4}\b/',
            '/\b\d{8}\b/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $normalized, $matches, PREG_OFFSET_CAPTURE) !== 1) {
                continue;
            }

            foreach ($matches[0] as $match) {
                $raw = (string) $match[0];
                $normalizedDate = $this->normalizeDateToYmd($raw);
                if ($normalizedDate === null && strlen($raw) === 8) {
                    $normalizedDate = $this->parseGarbledDate($raw);
                }

                if ($normalizedDate === null) {
                    continue;
                }

                $candidates[] = [
                    'date' => $normalizedDate,
                    'offset' => (int) $match[1],
                ];
            }
        }

        usort($candidates, fn (array $a, array $b) => $a['offset'] <=> $b['offset']);

        return $candidates;
    }

    /**
     * @param list<string> $allDates
     */
    private function bestIssueDateCandidate(array $allDates, ?string $dateOfBirth, string $validUntil): ?string
    {
        $currentYear = (int) date('Y');
        $validDate = strtotime(str_replace('/', '-', $validUntil));
        if ($validDate === false) {
            return null;
        }

        $candidates = [];
        foreach ($allDates as $date) {
            if ($date === $dateOfBirth || $date === $validUntil) {
                continue;
            }

            $year = (int) substr($date, 0, 4);
            if ($year < 2010 || $year > $currentYear) {
                continue;
            }

            $candidateTs = strtotime(str_replace('/', '-', $date));
            if ($candidateTs === false || $candidateTs > $validDate) {
                continue;
            }

            $candidates[] = $date;
        }

        if ($candidates === []) {
            return null;
        }

        usort($candidates, fn (string $a, string $b) => strcmp($b, $a));

        return $candidates[0] ?? null;
    }

    /**
     * Extract QC ID number – very tolerant of spacing / separators.
     * QC ID numbers are ALL digits, formatted as NNN NNN NNNNNNNN.
     */
    private function extractIdNumber(string $normalized, array $lines = []): ?string
    {
        $resolved = $this->resolveIdByAmbiguousDigitHeuristic($normalized, $lines);
        if ($resolved !== null) {
            return $resolved;
        }

        // Standard: 005 000 01257419  (3-3-6/8 or continuous 12-14)
        if (preg_match('/\b(\d{3}\s*\d{3}\s*\d{5,8})\b/', $normalized, $m)) {
            return $this->formatIdNumber($m[1]);
        }

        // Flexible separators and tail length (OCR often inserts dots/dashes).
        if (preg_match('/\b(\d{3}\D*\d{3}\D*\d{6,8})\b/', $normalized, $m)) {
            $formatted = $this->formatIdNumber($m[1]);
            if ($formatted !== null) {
                return $formatted;
            }
        }

        // Continuous long digit string (12-14 digits)
        if (preg_match('/\b(\d{12,14})\b/', $normalized, $m)) {
            return $this->formatIdNumber($m[1]);
        }

        // Digits with possible OCR artifacts (dashes, dots)
        if (preg_match('/\b(\d{3}[\s\.\-]*\d{3}[\s\.\-]*\d{5,8})\b/', $normalized, $m)) {
            return $this->formatIdNumber($m[1]);
        }

        // Mixed alphanumeric 3+3+digits: apply OCR digit correction
        // e.g. "P05 000 DA257479" → "005 000 04257479"
        // or   "BOS BOB 01257479" → "805 808 01257479"
        if (preg_match('/\b([A-Z0-9]{2,4})\s+([A-Z0-9]{2,4})\s+([A-Z0-9]{5,9})\b/', $normalized, $m)) {
            $corrected = $this->ocrToDigits($m[1]) . ' ' . $this->ocrToDigits($m[2]) . ' ' . $this->ocrToDigits($m[3]);
            // Verify the corrected string is mostly digits
            $digitCount = preg_match_all('/\d/', $corrected);
            $totalChars = strlen(preg_replace('/\s/', '', $corrected));
            if ($digitCount >= $totalChars * 0.8) {
                return $this->formatIdNumber($corrected);
            }
        }

        // Line-based: search from the BOTTOM (ID is below QR code)
        $reversedLines = array_reverse($lines);
        foreach ($reversedLines as $line) {
            $tokens = preg_split('/\s+/', trim($line)) ?: [];
            if (count($tokens) >= 3) {
                $lastThree = array_slice($tokens, -3);
                if (preg_match('/\d{5,}/', $lastThree[2])) {
                    $formatted = $this->formatIdNumber(implode(' ', $lastThree));
                    if ($formatted !== null) {
                        return $formatted;
                    }
                }
            }

            if (preg_match('/\b([A-Z0-9]{2,4}\s+[A-Z0-9]{2,4}\s+[A-Z0-9]{5,9})\b/', $line, $m)) {
                $formatted = $this->formatIdNumber($m[1]);
                if ($formatted !== null) {
                    return $formatted;
                }
            }

            // Pure digit pattern
            if (preg_match('/\b(\d{3}\s*\d{3}\s*\d{5,8})\b/', $line, $m)) {
                return $this->formatIdNumber($m[1]);
            }

            // Mixed alphanumeric → correct to digits
            if (preg_match('/([A-Z0-9]{2,4})\s+([A-Z0-9]{2,4})\s+([A-Z0-9]{5,9})/', $line, $m)) {
                $corrected = $this->ocrToDigits($m[1]) . ' ' . $this->ocrToDigits($m[2]) . ' ' . $this->ocrToDigits($m[3]);
                $digitCount = preg_match_all('/\d/', $corrected);
                $totalChars = strlen(preg_replace('/\s/', '', $corrected));
                if ($digitCount >= $totalChars * 0.8) {
                    return $this->formatIdNumber($corrected);
                }
            }

            // Correct full line then find digit pattern
            $correctedLine = $this->ocrToDigits($line);
            if (preg_match('/\b(\d{3}\s*\d{3}\s*\d{5,8})\b/', $correctedLine, $m)) {
                return $this->formatIdNumber($m[1]);
            }

            // Isolated long digit sequence (8+ digits) at end of line
            if (preg_match('/(\d{8,14})\s*$/', trim($correctedLine), $m)) {
                $formatted = $this->formatIdNumber($m[1]);
                if ($formatted !== null) {
                    return $formatted;
                }
            }

            // Loose grouped fallback from bottom lines (most likely ID strip).
            if (preg_match('/(\d{3})\D{0,4}(\d{3})\D{0,4}(\d{6,8})/', $correctedLine, $m)) {
                $formatted = $this->formatIdNumber($m[1] . $m[2] . $m[3]);
                if ($formatted !== null) {
                    return $formatted;
                }
            }
        }

        // OCR letter↔digit confusion on the full text: correct then match.
        // Keep this after the bottom-line search so it does not win with
        // incidental digit runs from the rest of the card.
        $ocrCorrected = $this->ocrToDigits($normalized);
        if (preg_match('/\b(\d{3}\s*\d{3}\s*\d{5,8})\b/', $ocrCorrected, $m)) {
            return $this->formatIdNumber($m[1]);
        }

        // Last resort: find longest digit sequence (7+) in corrected text
        if (preg_match_all('/\d{8,14}/', $ocrCorrected, $m)) {
            $formatted = $this->formatIdNumber((string) end($m[0]));
            if ($formatted !== null) {
                return $formatted;
            }
        }

        return null;
    }

    private function resolveIdByAmbiguousDigitHeuristic(string $normalized, array $lines = []): ?string
    {
        $candidates = [];

        if (preg_match_all('/\b(\d{3}\s*\d{3}\s*\d{8}|\d{13,14})\b/', $this->ocrToDigits($normalized), $matches)) {
            foreach ($matches[1] as $match) {
                $formatted = $this->formatIdNumber((string) $match);
                if ($formatted !== null) {
                    $candidates[] = $formatted;
                }
            }
        }

        $tail = implode(' ', array_slice(array_reverse($lines), 0, 10));
        if ($tail !== '' && preg_match_all('/\b(\d{3}\s*\d{3}\s*\d{8}|\d{13,14})\b/', $this->ocrToDigits($tail), $tailMatches)) {
            foreach ($tailMatches[1] as $match) {
                $formatted = $this->formatIdNumber((string) $match);
                if ($formatted !== null) {
                    $candidates[] = $formatted;
                    $candidates[] = $formatted; // weight bottom-strip matches more heavily
                }
            }
        }

        if (count($candidates) < 2) {
            return null;
        }

        $digitCandidates = array_values(array_filter(array_map(
            fn (string $candidate): string => preg_replace('/\D/', '', $candidate) ?? '',
            $candidates
        ), fn (string $digits): bool => strlen($digits) === 14));

        if (count($digitCandidates) < 2) {
            return null;
        }

        foreach ($digitCandidates as $a) {
            foreach ($digitCandidates as $b) {
                if ($a === $b) {
                    continue;
                }

                $diffCount = 0;
                $diffIndex = -1;
                for ($i = 0; $i < 14; $i++) {
                    if ($a[$i] !== $b[$i]) {
                        $diffCount++;
                        $diffIndex = $i;
                    }
                }

                if ($diffCount === 1 && $diffIndex === 6) {
                    if ($a[6] === '0' && preg_match('/[3689]/', $b[6])) {
                        return $this->formatIdNumber($a);
                    }
                    if ($b[6] === '0' && preg_match('/[3689]/', $a[6])) {
                        return $this->formatIdNumber($b);
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractCardholderName(string $normalized, array $lines): ?string
    {
        // Strategy 1: Look for the line ABOVE the Sex/DOB/Civil Status labels.
        foreach ($lines as $index => $line) {
            if (preg_match('/\b(?:SEX|DATE\s*OF\s*BIRTH|CIVIL\s*STATUS)\b/', $line)) {
                for ($offset = 1; $offset <= 4; $offset++) {
                    $idx = $index - $offset;
                    if ($idx >= 0) {
                        $candidate = trim($lines[$idx]);
                        if ($candidate === '' || $this->looksLikeLabel($candidate)) continue;
                        if (preg_match('/[A-Z]{2,}/', $candidate) && mb_strlen($candidate) >= 5) {
                            if (preg_match('/^\d[\d\/\-\s]+$/', $candidate)) continue;
                            if (preg_match('/^\d{2,}\s/', $candidate)) continue;
                            return $this->cleanName($candidate);
                        }
                    }
                }
            }
        }

        // Strategy 2: Line after LAST NAME/FIRST NAME label
        foreach ($lines as $index => $line) {
            if (preg_match('/LAST\s*NAME.*FIRST\s*NAME|FIRST\s*NAME.*LAST\s*NAME/', $line)) {
                $nextLine = $lines[$index + 1] ?? '';
                if ($nextLine !== '' && !$this->looksLikeLabel($nextLine) && preg_match('/[A-Z]{2,}/', $nextLine)) {
                    return $this->cleanName($nextLine);
                }
            }
        }

        // Strategy 3: LAST NAME, FIRST NAME, MIDDLE NAME followed by name text
        if (preg_match('/LAST\s*NAME,?\s*FIRST\s*NAME,?\s*MIDDLE\s*NAME\s+([A-Z][A-Z\s,\.\-]{4,}?)\b/', $normalized, $matches) === 1) {
            return $this->cleanName($matches[1]);
        }

        // Strategy 4: Comma-separated (SURNAME, FIRST MIDDLE) — QC ID standard format
        foreach ($lines as $line) {
            if ($this->looksLikeLabel($line)) continue;
            $trimmed = trim($line);
            if (preg_match('/^([A-Z]{2,}),\s*([A-Z][A-Z\s\.]{2,})$/', $trimmed, $m)) {
                if (!preg_match('/\b(?:CITY|STREET|BARANGAY|QUEZON|REPUBLIC|ADDRESS|CARD)\b/', $m[0])) {
                    return $this->cleanName($m[0]);
                }
            }
        }

        // Strategy 5: Multi-word uppercase line (FIRST LAST or FIRST MIDDLE LAST)
        foreach ($lines as $line) {
            if ($this->looksLikeLabel($line)) continue;
            $trimmed = trim($line);
            if (preg_match('/^[A-Z][A-Z\s,\.\-]+$/', $trimmed) && mb_strlen($trimmed) >= 5) {
                $words = preg_split('/[\s,]+/', $trimmed, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                $alphaWords = array_filter($words, fn ($w) => preg_match('/^[A-Z]{2,}$/', $w));
                if (count($alphaWords) >= 2 && count($words) <= 6) {
                    if (preg_match('/\b(?:CITY|STREET|BARANGAY|QUEZON|REPUBLIC|KASAMA|CITIZEN|CARDHOLDER|SIGNATURE|ADDRESS|EMERGENCY)\b/', $trimmed)) continue;
                    return $this->cleanName($trimmed);
                }
            }
        }

        // Strategy 6: Comma-name pattern anywhere in normalized text
        if (preg_match('/\b([A-Z]{2,}),\s*([A-Z]{2,}(?:\s+[A-Z\.]{1,}){0,3})\b/', $normalized, $m)) {
            if (!preg_match('/\b(?:CITY|QUEZON|REPUBLIC|KASAMA|CITIZEN|ADDRESS)\b/', $m[0])) {
                return $this->cleanName($m[0]);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractAddress(array $lines, string $normalized, array $knownDates = []): ?string
    {
        // Strategy 1: Explicit ADDRESS label followed by multiline capture
        foreach ($lines as $index => $line) {
            if (preg_match('/\bADDRESS\b/', $line)) {
                $addressParts = [];
                // Increase offset to 5 to handle sparse OCR lines
                for ($offset = 0; $offset <= 5; $offset++) {
                    $idx = $index + $offset;
                    if (!isset($lines[$idx])) break;
                    
                    $candidate = $lines[$idx];
                    if ($offset === 0) $candidate = trim(preg_replace('/\bADDRESS\b/', '', $candidate));
                    
                    if ($candidate && !$this->looksLikeLabel($candidate)) {
                        $addressParts[] = $candidate;
                    }
                    if (preg_match('/QUEZON\s*(CITY|C\s*ITY|C1TY|ITY)/i', $candidate)) break;
                }
                if ($addressParts) return $this->cleanAddress(implode(', ', $addressParts), $knownDates);
            }
        }

        // Strategy 2: Search for address markers (PUROK, BARANGAY, etc.) or Quezon City Barangay Anchors
        $barangayAnchors = 'BAGBAG|NOVALICHES|KINGSPOINT|FAIRVIEW|COMMONWEALTH|BATASAN|GULOD|SAN\s*BARTOLOME|TALIPAPA|PAYATAS|CUBAO|PROJECT\s*[4678]|MATANDANG\s*BALARA|PASONG\s*TAMO|HOLY\s*SPIRIT|TANDANG\s*SORA|BAESA';
        
        foreach ($lines as $index => $line) {
            $isLocationMarker = preg_match('/\b(PUROK|BARANGAY|BRGY|SITIO|STREET|EXT|AVE|AVENUE|UNIT|BLK|LOT|PHASE|SUBD|VILLAGE)\b/i', $line);
            $isBarangayAnchor = preg_match('/\b(' . $barangayAnchors . ')\b/i', $line);

            if ($isLocationMarker || $isBarangayAnchor) {
                $addressParts = [];
                // Look forward 4 lines for the city anchor
                for ($offset = 0; $offset <= 4; $offset++) {
                    $idx = $index + $offset;
                    if (!isset($lines[$idx])) break;
                    $candidate = $lines[$idx];
                    
                    if ($candidate && !$this->looksLikeLabel($candidate)) {
                        $addressParts[] = $candidate;
                    }
                    
                    // Break if we find the city anchor
                    if (preg_match('/QUEZON\s*(CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)/i', $candidate)) break;
                }
                
                if ($addressParts) {
                    $combined = implode(', ', $addressParts);
                    // If we found a barangay anchor but no city, add the city for completeness
                    if ($isBarangayAnchor && !preg_match('/QUEZON\s*CITY/i', $combined)) {
                        $combined .= ', QUEZON CITY';
                    }
                    return $this->cleanAddress($combined, $knownDates);
                }
            }
        }

        // Strategy 3: Search for QUEZON CITY anchor
        foreach ($lines as $index => $line) {
            if (preg_match('/QUEZON\s*(CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)/i', $line)) {
                $parts = [];
                for ($back = 4; $back >= 1; $back--) {
                    $prev = $lines[$index - $back] ?? null;
                    if ($prev && !$this->looksLikeLabel($prev)) $parts[] = $prev;
                }
                $parts[] = $line;
                return $this->cleanAddress(implode(', ', array_unique($parts)), $knownDates);
            }
        }

        return null;
    }

    /**
     * Prefer the strict 3-3-8 digit pattern from the lower card strip.
     *
     * @param list<string> $lines
     */
    private function extractStrictBottomIdCandidate(array $lines): ?string
    {
        if ($lines === []) {
            return null;
        }

        $tail = array_slice($lines, -12);

        for ($i = count($tail) - 1; $i >= 0; $i--) {
            $line = $this->ocrToDigits((string) $tail[$i]);

            if (preg_match('/\b(\d{3})\D{0,4}(\d{3})\D{0,4}(\d{8})\b/', $line, $m) === 1) {
                $formatted = $this->formatIdNumber($m[1] . $m[2] . $m[3]);
                if ($formatted !== null) {
                    return $formatted;
                }
            }
        }

        return null;
    }

    private function extractMatch(string $pattern, string $subject): ?string
    {
        if (preg_match($pattern, $subject, $matches) !== 1) {
            return null;
        }

        return trim($matches[1]);
    }

    private function cleanName(string $value): string
    {
        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
        $value = trim($value, ' .,');

        // Remove known OCR noise labels that may appear in a name line
        $value = preg_replace('/\b(?:LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|CARDHOLDER|SIGNATURE)\b/', '', $value) ?? $value;
        $value = trim(preg_replace('/\s+/', ' ', $value) ?? $value);

        // OCR sometimes prefixes a stray single character before
        // surname-based names (e.g. "A JIBE, MICCO JIRO FRUELDA").
        $value = preg_replace('/^[A-Z0-9]\s+(?=[A-Z]{2,},\s*[A-Z])/', '', $value) ?? $value;

        // Remove trailing pure digit noise (e.g. "CALINAWAN 3")
        $value = preg_replace('/\s+\d+$/', '', $value) ?? $value;

        // Only remove trailing single char if it's a digit (preserve middle initials)
        $parts = preg_split('/\s+/', $value) ?: [];
        if (count($parts) >= 3) {
            $last = end($parts);
            if ($last !== false && strlen($last) === 1 && ctype_digit($last)) {
                array_pop($parts);
                $value = implode(' ', $parts);
            }
        }

        return trim($value, ' ,.-');
    }

    /**
     * Specifically repair common QC OCR artifacts like "ITY" or "INGSPOINT".
     */
    private function fixOcrNoise(string $text): string
    {
        if (empty($text)) return $text;

        // Fix "QUEZON CITY" misreads and prevent doubling
        // This regex looks for any garbled version of "QUEZON CITY" at the end and replaces it entirely
        $text = preg_replace('/\b(?:QUEZON\s*)?(?:QUEZON\s*)?(?:CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)\b$/i', ' QUEZON CITY', $text);
        
        // Remove duplicate "QUEZON" if it appears twice due to OCR overlap
        $text = preg_replace('/\b(QUEZON)\s+\1\b/i', '$1', $text);
        
        // Fix specific local misreads
        $text = preg_replace('/\b(?:K\s*)?INGS?POINT\b/i', 'KINGSPOINT', $text);
        $text = preg_replace('/\b(?:B\s*)?AGBAG\b/i', 'BAGBAG', $text);
        
        return preg_replace('/,\s*,/', ',', $text);
    }

    private function cleanAddress(string $value, array $knownDates = []): string
    {
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[^A-Z0-9,\.\-\s]/u', ' ', $value) ?? $value;

        // Remove label/header text that OCR often mixes in
        $value = preg_replace('/\b(?:ADDRESS|CARDHOLDER|SIGNATURE|EMERGENCY|CONTACT|RELAY|GNATURE|DATE\s*ISSUED|VALID\s*UNTIL|DATE\s*(?:OF)?\s*BIRTH|CIVIL\s*STATUS|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|Q\s*CITIZEN\s*CARD|LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|SEX|IN\s*CASE\s*OF|BLOOD\s*TYPE|KASAMA\s*KA\s*SA\s*PAG\s*UNLAD)\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\bREPUBLIC\s+OF\s+THE\s+[A-Z]{1,20}\b/', ' ', $value) ?? $value;

        // Remove dates (YYYY/MM/DD or MM/DD/YYYY patterns)
        $value = preg_replace('/\b\d{4}[\/-]\d{2}[\/-]\d{2}\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\b\d{2}[\/-]\d{2}[\/-]\d{4}\b/', ' ', $value) ?? $value;
        
        // Remove space-separated dates (YYYY MM DD or MM DD YYYY)
        $value = preg_replace('/\b\d{4}\s\d{2}\s\d{2}\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\b\d{2}\s\d{2}\s\d{4}\b/', ' ', $value) ?? $value;

        // Strip specific known dates if they sit in the string
        foreach ($knownDates as $date) {
            if (!$date) continue;
            $numericOnly = preg_replace('/\D/', '', $date);
            if (strlen($numericOnly) === 8) {
                // Try YYYY MM DD pieces
                $y = substr($numericOnly, 0, 4);
                $m = substr($numericOnly, 4, 2);
                $d = substr($numericOnly, 6, 2);
                $value = str_replace(["$y $m $d", "$m $d $y", $numericOnly], ' ', $value);
            }
        }

        // Remove phone numbers (e.g. 0998 954 6210)
        $value = preg_replace('/\b0\d{3}\s*\d{3}\s*\d{4}\b/', ' ', $value) ?? $value;
        $value = preg_replace('/\b09\d{9}\b/', ' ', $value) ?? $value;

        // Remove emergency contact names (pattern: SURNAME, FIRST V.)
        $value = preg_replace('/[A-Z]{2,},\s*[A-Z]{2,}\s*[A-Z]\.?\s*/', ' ', $value) ?? $value;

        $value = preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);

        // Anchor on street/address number pattern before QUEZON CITY (fuzzy)
        $cityPattern = 'QUEZON\s*(?:CITY|C\s*ITY|C1TY|1TY|ITY|LITY|CTY)';
        if (preg_match('/(\d{1,5}\s*[A-Z]?\s+[A-Z][A-Z0-9\s,\.\-]*?'.$cityPattern.')/i', $value, $m)) {
            $value = $m[1];
        } elseif (preg_match('/((?:BLK\-?\d*|LOT\-?\d*|UNIT\-?\d*|#\d+|PUROK\s*\d+|BRGY\s*[A-Z]+)\s*[A-Z0-9,\.\-\s]+?'.$cityPattern.')/i', $value, $m)) {
            $value = $m[1];
        } elseif (preg_match('/([A-Z0-9,\.\-\s]+?'.$cityPattern.')/i', $value, $m)) {
            $value = $m[1];
        }

        // Standardize QUEZON CITY at the end
        $value = preg_replace('/'.$cityPattern.'$/', 'QUEZON CITY', $value);

        $value = preg_replace('/\s*,\s*/', ', ', $value) ?? $value;
        $value = preg_replace('/\s{2,}/', ' ', $value) ?? $value;
        $value = trim($value, ' ,.-');

        return $this->fixOcrNoise($value);
    }

    private function looksPlausibleAddress(?string $value): bool
    {
        if (! is_string($value) || $value === '') {
            return false;
        }

        if (! str_contains($value, 'QUEZON CITY')) {
            return false;
        }

        if (preg_match('/\b(LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|DATE\s*ISSUED|VALID\s*UNTIL|CIVIL\s*STATUS|CARDHOLDER|SIGNATURE)\b/', $value) === 1) {
            return false;
        }

        return strlen($value) >= 12;
    }

    private function looksLikeLabel(string $value): bool
    {
        // Add pattern to exclude rows that are clearly just values sitting above labels
        // e.g. "M SINGLE 10/01/2003" sits above "SEX CIVIL STATUS DATE OF BIRTH"
        if (preg_match('/^(?:[MF]\s+)?(?:SINGLE|MARRIED|WIDOWED|DIVORCED|SEPARATED)\s+\d{2,4}/i', $value)) {
            return true;
        }

        return preg_match('/LAST\s*NAME|FIRST\s*NAME|MIDDLE\s*NAME|DATE\s*(OF)?\s*BIRTH|DATE\s*ISSUED|VALID\s*UNTIL|\bSEX\b|CIVIL\s*STATUS|CARD\s*HOLDER\s*S?\s*IGNATURE|REPUBLIC\s+OF\s+THE\s+PHILIPPINES|CITIZEN\s*CARD|KASAMA\s+KA\s+SA\s+PAG/', $value) === 1;
    }

    /**
     * @return list<string>
     */
    private function nameTokens(string $value): array
    {
        $value = mb_strtoupper($value, 'UTF-8');
        $value = preg_replace('/[^A-Z\s]/u', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;
        $tokens = explode(' ', trim($value));

        return array_values(array_filter($tokens, fn (string $token) => strlen($token) >= 2));
    }

    // ────────────────────────────────────────────────────────────────────
    // QC ID layout-aware extraction
    // ────────────────────────────────────────────────────────────────────

    /**
     * Extract fields from the QC ID's "value above label" layout.
     *
     * QC ID cards print values in a row ABOVE their labels:
     *   M  SINGLE  2003/01/01        (values)
     *   SEX  CIVIL STATUS  DATE OF BIRTH  (labels)
     *
     * OCR reads top-to-bottom, so values appear before labels in the text.
     *
     * @param  list<string>  $lines
     * @return array<string, string|null>
     */
    private function extractDate(string $normalized, array $lines = []): ?string
    {
        // Strategy 1: Scrub the normalized text to find any date-like string.
        // Handling the "straight line" problem (OCR misreading / or | as 1).
        $scrubbed = preg_replace('/[\|1I]/', '/', $normalized) ?? $normalized;
        $scrubbed = preg_replace('/\s+/', '', $scrubbed) ?? $scrubbed;

        // Pattern: YYYY/MM/DD or DD/MM/YYYY with flexible separators
        if (preg_match('/(\d{4}[\/\-]\d{2}[\/\-]\d{2})|(\d{2}[\/\-]\d{2}[\/\-]\d{4})/', $scrubbed, $m)) {
            return $this->formatDateString($m[0]);
        }

        // Strategy 2: Look for 8 digits in a row and try to format as YYYYMMDD
        if (preg_match('/(\d{8})/', $normalized, $m)) {
            return $this->formatDateString($m[1]);
        }

        // Strategy 3: Search lines specifically for dates
        foreach ($lines as $line) {
            $lineScrubbed = preg_replace('/[\|1I]/', '/', $line);
            if (preg_match('/(\d{2,4}[\/\-]\d{1,2}[\/\-]\d{2,4})/', $lineScrubbed, $m)) {
                return $this->formatDateString($m[0]);
            }
        }

        return null;
    }

    private function formatDateString(string $date): ?string
    {
        $date = str_replace('-', '/', $date);
        $parts = explode('/', $date);
        
        if (count($parts) === 3) {
            if (strlen($parts[0]) === 4) return "$parts[0]/$parts[1]/$parts[2]";
            if (strlen($parts[2]) === 4) return "$parts[2]/$parts[0]/$parts[1]";
        }
        
        if (strlen($date) === 8) {
             return substr($date, 0, 4) . '/' . substr($date, 4, 2) . '/' . substr($date, 6, 2);
        }
        
        return null;
    }

    private function extractQcIdLayoutFields(string $normalized, array $lines): array
    {
        $fields = [];
        $civilStatuses = 'SINGLE|MARRIED|WIDOW(?:ED)?|SEPARATED|DIVORCED|ANNULLED';
        $datePat = '(\d{2,4}[\/\-]\d{2,4}[\/\-]\d{2,4})';
        $datePat8 = '(\d{8})';

        // ── Normalized-text patterns ───
        // QC ID actual column order: SEX  DATE_OF_BIRTH  CIVIL_STATUS
        // So OCR produces: M 2003/01/01 SINGLE ... SEX DATE OF BIRTH

        // Pattern A: M DATE CIVIL_STATUS ... SEX (actual QC ID order)
        if (preg_match(
            '/\b([MF])\s+' . $datePat . '\s+(' . $civilStatuses . ')\b/',
            $normalized, $m
        )) {
            $fields['sex'] = $m[1];
            $fields['date_of_birth'] = $m[2];
            $fields['civil_status'] = $m[3];
        }

        // Pattern A2: M 8-digit-date CIVIL_STATUS (no separators)
        if (! isset($fields['sex']) && preg_match(
            '/\b([MF])\s+' . $datePat8 . '\s+(' . $civilStatuses . ')\b/',
            $normalized, $m
        )) {
            $fields['sex'] = $m[1];
            $fields['date_of_birth'] = substr($m[2], 0, 4) . '/' . substr($m[2], 4, 2) . '/' . substr($m[2], 6, 2);
            $fields['civil_status'] = $m[3];
        }

        // Pattern B: M CIVIL_STATUS DATE ... SEX (alternate order)
        if (! isset($fields['sex']) && preg_match(
            '/\b([MF])\s+(' . $civilStatuses . ')\s+' . $datePat . '/',
            $normalized, $m
        )) {
            $fields['sex'] = $m[1];
            $fields['civil_status'] = $m[2];
            $fields['date_of_birth'] = $m[3];
        }

        // Pattern C: M GARBLED_DATE CIVIL_STATUS (e.g. M 20030/01 SINGLE)
        if (! isset($fields['sex']) && preg_match(
            '/\b([MF])\s+(\d[\d\/\-\s]{5,12}?\d)\s+(' . $civilStatuses . ')\b/',
            $normalized, $m
        )) {
            $fields['sex'] = $m[1];
            $fields['civil_status'] = $m[3];
            // Try to parse the garbled date
            $dateCandidate = $this->parseGarbledDate($m[2]);
            if ($dateCandidate !== null) {
                $fields['date_of_birth'] = $dateCandidate;
            }
        }

        // Pattern D: sex + DOB only (no civil status parsed)
        if (! isset($fields['sex']) && preg_match(
            '/\b([MF])\s+' . $datePat . '/',
            $normalized, $m
        )) {
            $fields['sex'] = $m[1];
            if (! isset($fields['date_of_birth'])) {
                $fields['date_of_birth'] = $m[2];
            }
        }

        if (! isset($fields['blood_type']) && preg_match('/\b([ABO0]{1,2}\s*[\+\-])\s+\d{2,4}[\/\-]?\d{2,4}/', $normalized, $m)) {
            $fields['blood_type'] = str_replace('0', 'O', strtoupper(preg_replace('/\s+/', '', $m[1]) ?? $m[1]));
        }

        // Civil status standalone (before SEX label)
        if (! isset($fields['civil_status']) && preg_match(
            '/\b(' . $civilStatuses . ')\b.*?\bSEX\b.*?CIVIL\s*STATUS/',
            $normalized, $m
        )) {
            $fields['civil_status'] = $m[1];
        }

        // Date Issued + Valid Until: DATE1 DATE2 ... DATE ISSUED
        if (preg_match(
            '/' . $datePat . '\s+' . $datePat . '\s+.*?DATE\s*ISSUED/',
            $normalized, $m
        )) {
            $fields['date_issued'] = $m[1];
            $fields['valid_until'] = $m[2];
        }

        // Date Issued + Valid Until: 8-digit dates
        if (! isset($fields['date_issued']) && preg_match(
            '/' . $datePat8 . '\s+' . $datePat8 . '\s+.*?DATE\s*ISSUED/',
            $normalized, $m
        )) {
            $fields['date_issued'] = substr($m[1], 0, 4) . '/' . substr($m[1], 4, 2) . '/' . substr($m[1], 6, 2);
            $fields['valid_until'] = substr($m[2], 0, 4) . '/' . substr($m[2], 4, 2) . '/' . substr($m[2], 6, 2);
        }

        // DATE1 DATE2 anywhere before or near DATE ISSUED / VALID UNTIL labels
        // Catches: 2024/02/15 2034/10/01 ... DATE ISSUED VALID UNTIL
        if (! isset($fields['date_issued'])) {
            if (preg_match_all('/' . $datePat . '/', $normalized, $allM, PREG_OFFSET_CAPTURE)) {
                // Find dates that are NOT the DOB
                $nonDobDates = [];
                foreach ($allM[1] as $dateMatch) {
                    $dateVal = $dateMatch[0];
                    if (isset($fields['date_of_birth']) && $dateVal === $fields['date_of_birth']) {
                        continue;
                    }
                    $nonDobDates[] = $dateVal;
                }
                // If we found exactly 2 non-DOB dates, assign them
                if (count($nonDobDates) >= 2) {
                    $fields['date_issued'] = $nonDobDates[0];
                    $fields['valid_until'] = $nonDobDates[1];
                } elseif (count($nonDobDates) === 1) {
                    // Single extra date: guess by year
                    $year = (int) substr($nonDobDates[0], 0, 4);
                    $currentYear = (int) date('Y');
                    if ($year > $currentYear) {
                        $fields['valid_until'] = $nonDobDates[0];
                    } else {
                        $fields['date_issued'] = $nonDobDates[0];
                    }
                }
            }
        }

        // ── Line-based patterns ───

        foreach ($lines as $i => $line) {
            // Label line containing SEX + (CIVIL STATUS or DATE OF BIRTH)
            if (preg_match('/\bSEX\b/', $line) && preg_match('/DATE\s*(OF)?\s*BIRTH|CIVIL\s*STATUS/', $line)) {
                $prevLine = $lines[$i - 1] ?? '';

                if (! isset($fields['sex']) && preg_match('/\b([MF])\b/', $prevLine, $m)) {
                    $fields['sex'] = $m[1];
                }

                if (! isset($fields['civil_status']) && preg_match('/\b(' . $civilStatuses . ')\b/', $prevLine, $m)) {
                    $fields['civil_status'] = strtoupper($m[1]);
                }

                if (! isset($fields['date_of_birth'])) {
                    if (preg_match('/' . $datePat . '/', $prevLine, $m)) {
                        $fields['date_of_birth'] = $m[1];
                    } elseif (preg_match('/(\d{8})/', $prevLine, $m)) {
                        $raw = $m[1];
                        $fields['date_of_birth'] = substr($raw, 0, 4) . '/' . substr($raw, 4, 2) . '/' . substr($raw, 6, 2);
                    } else {
                        // Garbled date (e.g. 20030/01, 2003/0101)
                        if (preg_match('/(\d[\d\/\-]{4,10}\d)/', $prevLine, $m)) {
                            $parsed = $this->parseGarbledDate($m[1]);
                            if ($parsed !== null) {
                                $fields['date_of_birth'] = $parsed;
                            }
                        }
                    }
                }
            }

            // Label line containing DATE ISSUED
            if (preg_match('/DATE\s*ISSUED/', $line)) {
                $prevLine = $lines[$i - 1] ?? '';

                if (! isset($fields['blood_type']) && preg_match('/\b([ABO0]{1,2}\s*[\+\-])\b/', $prevLine, $bloodMatch)) {
                    $fields['blood_type'] = str_replace('0', 'O', strtoupper(preg_replace('/\s+/', '', $bloodMatch[1]) ?? $bloodMatch[1]));
                }

                // Compact date pairs like "2022/1205 2032/0825" above DATE ISSUED labels.
                if (preg_match('/(\d{4}[\/\-]\d{4})\s+(\d{4}[\/\-]\d{4})/', $prevLine, $compactPair)) {
                    $issuedFromCompact = $this->normalizeDateToYmd($compactPair[1]);
                    $validFromCompact = $this->normalizeDateToYmd($compactPair[2]);

                    if ($issuedFromCompact !== null && ! isset($fields['date_issued'])) {
                        $fields['date_issued'] = $issuedFromCompact;
                    }

                    if ($validFromCompact !== null && (! isset($fields['valid_until']) || (isset($fields['date_issued']) && $fields['valid_until'] === $fields['date_issued']))) {
                        $fields['valid_until'] = $validFromCompact;
                    }
                }

                // Standard dates with separators
                if (preg_match_all('/' . $datePat . '/', $prevLine, $m)) {
                    if (! isset($fields['date_issued']) && isset($m[1][0])) {
                        $fields['date_issued'] = $m[1][0];
                    }
                    if (! isset($fields['valid_until']) && isset($m[1][1])) {
                        $fields['valid_until'] = $m[1][1];
                    }
                }

                // 8-digit dates without separators
                if (! isset($fields['date_issued']) && preg_match_all('/(\d{8})/', $prevLine, $m)) {
                    if (isset($m[1][0])) {
                        $fields['date_issued'] = substr($m[1][0], 0, 4) . '/' . substr($m[1][0], 4, 2) . '/' . substr($m[1][0], 6, 2);
                    }
                    if (! isset($fields['valid_until']) && isset($m[1][1])) {
                        $fields['valid_until'] = substr($m[1][1], 0, 4) . '/' . substr($m[1][1], 4, 2) . '/' . substr($m[1][1], 6, 2);
                    }
                }

                // Garbled dates fallback for valid_until
                if (! isset($fields['valid_until'])) {
                    // Remove already-matched clean dates from the line, then look for garbled ones
                    $remaining = $prevLine;
                    if (isset($fields['date_issued'])) {
                        $remaining = str_replace(str_replace('/', '/', $fields['date_issued']), '', $remaining);
                    }
                    if (preg_match('/(\d[\d\/\-]{4,10}\d)/', $remaining, $m)) {
                        $parsed = $this->parseGarbledDate($m[1]);
                        if ($parsed !== null) {
                            $fields['valid_until'] = $parsed;
                        }
                    }
                }
            }

            // Separate VALID UNTIL label
            if (! isset($fields['valid_until']) && preg_match('/VALID\s*UNTIL/', $line) && ! preg_match('/DATE\s*ISSUED/', $line)) {
                $prevLine = $lines[$i - 1] ?? '';
                if (preg_match('/' . $datePat . '/', $prevLine, $m)) {
                    $fields['valid_until'] = $m[1];
                } elseif (preg_match('/(\d{8})/', $prevLine, $m)) {
                    $fields['valid_until'] = substr($m[1], 0, 4) . '/' . substr($m[1], 4, 2) . '/' . substr($m[1], 6, 2);
                }
            }
        }

        return $fields;
    }

    /**
     * Extract civil status – handles both label-before-value and value-before-label.
     */
    private function extractCivilStatus(string $normalized): ?string
    {
        $statuses = 'SINGLE|MARRIED|WIDOW(?:ED)?|SEPARATED|DIVORCED|ANNULLED';

        // Label followed by value
        if (preg_match('/CIVIL\s*STATUS\s*[:\s]*(' . $statuses . ')/', $normalized, $m)) {
            return $m[1];
        }

        // Standalone value near SEX or date context
        if (preg_match('/\b[MF]\s+(' . $statuses . ')\s+\d/', $normalized, $m)) {
            return $m[1];
        }

        // Fallback: known value anywhere in the text
        if (preg_match('/\b(' . $statuses . ')\b/', $normalized, $m)) {
            return $m[1];
        }

        return null;
    }

    // ────────────────────────────────────────────────────────────────────
    // Non-QC ID detection
    // ────────────────────────────────────────────────────────────────────

    /**
     * Detect if the text contains markers of a non-QC ID.
     *
     * @return string|null  The detected ID type name, or null.
     */
    private function detectNonQcId(string $normalized): ?string
    {
        $nonQcMarkers = [
            'Philippine National ID (PhilSys)' => '/PHIL\w*\s*NATIONAL\s*ID|PHILSYS|PHILIPPINE\s*IDENTIFICATION\s*SYSTEM|PSN\s*\d/',
            'Driver\'s License' => '/DRIVER\S?\s*S?\s*LICENSE|LAND\s*TRANSPORTATION|NON.?PROFESSIONAL\s*DRIVER|PROFESSIONAL\s*DRIVER|LTO\s*(?:ID|LICENSE)/',
            'PhilHealth ID' => '/PHILHEALTH|PHILIPPINE\s*HEALTH\s*INSURANCE|PHIC\b/',
            'SSS ID' => '/SOCIAL\s*SECURITY\s*SYSTEM|\bSSS\b\s*(?:MEMBER|NUMBER|ID)/',
            'UMID' => '/UNIFIED\s*MULTI.?PURPOSE\s*ID|U\.?M\.?I\.?D\b/',
            'TIN Card' => '/TAXPAYER\s*IDENTIFICATION|BUREAU\s*OF\s*INTERNAL\s*REVENUE|\bTIN\s*(?:ID|CARD|NO)/',
            'Philippine Passport' => '/PASSPORT\s*(?:NO|NUMBER)|DEPARTMENT\s*OF\s*FOREIGN\s*AFFAIRS|\bPASSPORT\b/',
            'PRC ID' => '/PROFESSIONAL\s*REGULATION|PRC\s*(?:ID|BOARD|LICENSE)/',
            'Postal ID' => '/POSTAL\s*(?:ID|IDENTIFICATION)|PHILIPPINE\s*POSTAL|PHILPOST|PHLPost/',
            'Voter\'s ID' => '/VOTER\S?\s*S?\s*(?:ID|IDENTIFICATION)|COMMISSION\s*ON\s*ELECTIONS|COMELEC/',
            'Senior Citizen ID' => '/SENIOR\s*CITIZEN\s*(?:ID|CARD)|\bOSCA\b/',
            'PWD ID' => '/PERSON\s*WITH\s*DISABILITY|\bPWD\s*(?:ID|CARD)|NATIONAL\s*COUNCIL\s*ON\s*DISABILITY/',
            'NBI Clearance' => '/NATIONAL\s*BUREAU\s*OF\s*INVESTIGATION|\bNBI\s*CLEARANCE/',
            'School ID' => '/STUDENT\s*(?:ID|IDENTIFICATION)|UNIVERSITY\s*(?:ID|IDENTIFICATION)|COLLEGE\s*(?:ID|IDENTIFICATION)/',
            'Barangay ID' => '/BARANGAY\s*(?:CLEARANCE|CERTIFICATE|ID)|BARANGAY\s*HALL|PUNONG\s*BARANGAY|BARANGAY\s*CAPTAIN/',
            'GSIS ID' => '/GOVERNMENT\s*SERVICE\s*INSURANCE|\bGSIS\b\s*(?:ID|MEMBER|CARD)/',
            'Company ID' => '/EMPLOYEE\s*(?:ID|IDENTIFICATION|NUMBER)|COMPANY\s*(?:ID|IDENTIFICATION)/',
            'OFW ID' => '/OVERSEAS\s*(?:FILIPINO|WORKER)|\bOFW\b\s*(?:ID|CARD)|\bOWWA\b/',
            'Police Clearance' => '/POLICE\s*CLEARANCE|PHILIPPINE\s*NATIONAL\s*POLICE|\bPNP\b\s*CLEARANCE/',
            'Birth Certificate' => '/CERTIFICATE\s*OF\s*(?:LIVE\s*)?BIRTH|CIVIL\s*REGISTRAR/',
            'Pag-IBIG ID' => '/PAG\s*-?\s*IBIG\s*(?:ID|FUND|MEMBER)|HDMF\s*(?:ID|MEMBER)/',
            'OWWA ID' => '/OVERSEAS\s*WORKERS\s*WELFARE|\bOWWA\s*(?:ID|CARD|MEMBER)/',
            'AFP ID' => '/ARMED\s*FORCES\s*OF\s*THE\s*PHILIPPINES|\bAFP\b\s*(?:ID|CARD)/',
            'IBP ID' => '/INTEGRATED\s*BAR\s*OF\s*THE\s*PHILIPPINES|\bIBP\b\s*(?:ID|CARD)/',
        ];

        foreach ($nonQcMarkers as $idType => $pattern) {
            if (preg_match($pattern, $normalized)) {
                return $idType;
            }
        }

        return null;
    }

    /**
     * Detect if the text contains markers of a fake or sample ID.
     */
    public function detectFakeId(string $normalized): bool
    {
        $fakeKeywords = [
            'SAMPLE',
            'SPECIMEN',
            'FAKE',
            'VOID',
            'TEMPLATE',
            'EXAMP',
            'NOT\s*VALID',
            'DO\s*NOT\s*USE',
            'TESTING\s*ONLY',
            'FOR\s*DEMO',
            'MOCK\s*UP',
            'DUMMY',
            'PROTOTYPE',
            'PLACEHOLDER',
            '1234567890', // Common placeholder
            '000\s*000\s*00000000', // Common placeholder ID
            '11111111111111',
            '99999999999999',
            '999\s*000\s*98765432\s*10',
            'JANE\s*DOE',
            'JOHN\s*DOE',
            'JUAN\s+DELA\s+CRUZ',
            'MARIA\s+CLARA',
            'JOSE\s+RIZAL',
            'TEST\s+USER',
            'SAMPLE\s+NAME',
            'INVALID',
            'FOR\s*SYSTEM\s*TESTING',
        ];

        foreach ($fakeKeywords as $kw) {
            if (preg_match('/\b' . $kw . '\b/i', $normalized)) {
                return true;
            }
        }

        return false;
    }
}
