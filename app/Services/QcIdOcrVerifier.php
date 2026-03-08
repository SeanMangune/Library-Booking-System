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

        return [
            'is_valid' => $looksLikeQcId,
            'confidence_score' => $confidenceScore,
            'matched_markers' => array_column($matchedMarkers, 'label'),
            'marker_count' => count($matchedMarkers),
            'cardholder_name' => $fields['cardholder_name'] ?? null,
            'sex' => $fields['sex'] ?? null,
            'date_of_birth' => $fields['date_of_birth'] ?? null,
            'civil_status' => $fields['civil_status'] ?? null,
            'date_issued' => $fields['date_issued'] ?? null,
            'valid_until' => $fields['valid_until'] ?? null,
            'address' => $fields['address'] ?? null,
            'id_number' => $fields['id_number'] ?? null,
            'name_matches' => $nameMatches,
            'normalized_text' => $normalized,
        ];
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
        $text = preg_replace('/[^A-Z0-9\/,&.\-\s]/u', ' ', $text) ?? $text;
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
            $line = preg_replace('/[^A-Z0-9\/,&.\-\s]/u', ' ', $line) ?? $line;
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
        return [
            'cardholder_name' => $this->extractCardholderName($normalized, $lines),
            'sex' => $this->extractSex($normalized),
            'date_of_birth' => $this->extractDate('DATE\s*(?:OF)?\s*BIRTH\s*[:\s]*', $normalized),
            'civil_status' => $this->extractMatch('/CIVIL\s*STATUS\s*[:\s]*([A-Z]{4,20})/', $normalized),
            'date_issued' => $this->extractDate('DATE\s*ISSUED\s*[:\s]*', $normalized),
            'valid_until' => $this->extractDate('VALID\s*UNTIL\s*[:\s]*', $normalized),
            'address' => $this->extractAddress($lines),
            'id_number' => $this->extractIdNumber($normalized),
        ];
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

        return null;
    }

    /**
     * Extract a date that follows $labelPattern. Tolerates multiple
     * date formats commonly produced by OCR.
     *
     * @param  string  $labelPattern  Raw regex fragment (no delimiters)
     */
    private function extractDate(string $labelPattern, string $normalized): ?string
    {
        // Try: LABEL  YYYY/MM/DD  or  YYYY-MM-DD
        if (preg_match('/' . $labelPattern . '(\d{4}[\/-]\d{2}[\/-]\d{2})/', $normalized, $m)) {
            return $m[1];
        }

        // Try: LABEL  MM/DD/YYYY  or  DD/MM/YYYY
        if (preg_match('/' . $labelPattern . '(\d{2}[\/-]\d{2}[\/-]\d{4})/', $normalized, $m)) {
            return $m[1];
        }

        // Try: LABEL followed by digits without separators (e.g. 20030101)
        if (preg_match('/' . $labelPattern . '(\d{8})/', $normalized, $m)) {
            $raw = $m[1];

            return substr($raw, 0, 4) . '/' . substr($raw, 4, 2) . '/' . substr($raw, 6, 2);
        }

        return null;
    }

    /**
     * Extract QC ID number – very tolerant of spacing / separators.
     */
    private function extractIdNumber(string $normalized): ?string
    {
        // Standard: 005 000 01257419  (3-3-7/8 or continuous 13-14)
        if (preg_match('/\b(\d{3}\s*\d{3}\s*\d{5,8})\b/', $normalized, $m)) {
            return trim($m[1]);
        }

        // Continuous long digit string (10-14 digits)
        if (preg_match('/\b(\d{10,14})\b/', $normalized, $m)) {
            return $m[1];
        }

        // Digits with possible OCR artifacts (dashes, dots)
        if (preg_match('/\b(\d{3}[\s\.\-]*\d{3}[\s\.\-]*\d{5,8})\b/', $normalized, $m)) {
            return preg_replace('/[^\d\s]/', '', $m[1]) ?? $m[1];
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractCardholderName(string $normalized, array $lines): ?string
    {
        // Strategy 1: LAST NAME, FIRST NAME, MIDDLE NAME followed by a
        // name-like string before the next field label.
        if (preg_match('/LAST\s*NAME,?\s*FIRST\s*NAME,?\s*MIDDLE\s*NAME\s+([A-Z][A-Z\s,\.\-]{5,}?)\s+(?:SEX|DATE\s+OF\s+BIRTH|CIVIL\s+STATUS)/', $normalized, $matches) === 1) {
            return $this->cleanName($matches[1]);
        }

        // Strategy 2: Line after "LAST NAME" / "FIRST NAME" label.
        foreach ($lines as $index => $line) {
            if (str_contains($line, 'LAST NAME') || str_contains($line, 'FIRST NAME')) {
                for ($offset = 1; $offset <= 3; $offset++) {
                    $candidate = $lines[$index + $offset] ?? null;
                    if (! $candidate || $this->looksLikeLabel($candidate)) {
                        continue;
                    }

                    if (preg_match('/[A-Z]{2,}/', $candidate) === 1 && mb_strlen($candidate) >= 5) {
                        return $this->cleanName($candidate);
                    }
                }
            }
        }

        // Strategy 3: Look for comma-separated name pattern (SURNAME, FIRST MIDDLE)
        foreach ($lines as $line) {
            if ($this->looksLikeLabel($line)) {
                continue;
            }

            if (preg_match('/^([A-Z]{2,}),\s*([A-Z]{2,}(?:\s+[A-Z]{2,})*)$/', trim($line), $m)) {
                return $this->cleanName($m[0]);
            }
        }

        // Strategy 4: Any line that is all-caps with 2+ words and 8+ chars.
        foreach ($lines as $line) {
            if ($this->looksLikeLabel($line)) {
                continue;
            }

            if (preg_match('/^[A-Z][A-Z\s,\.\-]{7,}$/', $line) === 1 && str_word_count(str_replace(',', ' ', $line)) >= 2) {
                return $this->cleanName($line);
            }
        }

        return null;
    }

    /**
     * @param  list<string>  $lines
     */
    private function extractAddress(array $lines): ?string
    {
        // Strategy 1: Line containing "QUEZON CITY"
        foreach ($lines as $index => $line) {
            if (! preg_match('/QUEZON\s*CITY/', $line)) {
                continue;
            }

            $parts = [];
            $previous = $lines[$index - 1] ?? null;
            $next = $lines[$index + 1] ?? null;

            if ($previous && ! $this->looksLikeLabel($previous) && ! str_contains($previous, 'VALID UNTIL')) {
                $parts[] = $previous;
            }

            $parts[] = $line;

            if ($next && ! $this->looksLikeLabel($next) && ! str_contains($next, 'IN CASE OF EMERGENCY')) {
                $parts[] = $next;
            }

            return trim(implode(' ', array_unique($parts)));
        }

        // Strategy 2: Line containing CONSTITUENCY or BARANGAY (common
        // in QC addresses) even if "QUEZON CITY" was garbled.
        foreach ($lines as $index => $line) {
            if (! preg_match('/CONSTITUENCY|BARANGAY|BRGY/', $line)) {
                continue;
            }

            $parts = [];
            $previous = $lines[$index - 1] ?? null;

            if ($previous && ! $this->looksLikeLabel($previous)) {
                $parts[] = $previous;
            }

            $parts[] = $line;

            $next = $lines[$index + 1] ?? null;
            if ($next && ! $this->looksLikeLabel($next)) {
                $parts[] = $next;
            }

            return trim(implode(' ', array_unique($parts)));
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

        return $value;
    }

    private function looksLikeLabel(string $value): bool
    {
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
}
