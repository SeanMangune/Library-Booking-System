<?php

namespace Tests\Unit;

use App\Services\QcIdOcrVerifier;
use PHPUnit\Framework\TestCase;

class QcIdOcrVerifierTest extends TestCase
{
    public function test_it_accepts_a_qc_citizen_id_ocr_dump(): void
    {
        $text = <<<'TEXT'
REPUBLIC OF THE PHILIPPINES
Q CITIZENCARD
Last Name, First Name, Middle Name
MANGUNE, SEAN MICHAEL CALINAWAN
Sex M    Date of Birth 2003/10/01    Civil Status SINGLE
Date Issued 2024/02/16    Valid Until 2034/10/01
19 A KING CONSTANTINE EXT
KINGSPOINT BAGBAG, QUEZON CITY
Cardholder Signature
005 000 01257479
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Sean Michael Mangune');

        $this->assertTrue($result['is_valid']);
        $this->assertSame('MANGUNE, SEAN MICHAEL CALINAWAN', $result['cardholder_name']);
        $this->assertSame('2003/10/01', $result['date_of_birth']);
        $this->assertTrue($result['name_matches']);
    }

    public function test_it_rejects_non_qc_id_text(): void
    {
        $text = <<<'TEXT'
STUDENT IDENTIFICATION CARD
QUEZON CITY UNIVERSITY
Name John Sample
Program BSIT
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'John Sample');

        $this->assertFalse($result['is_valid']);
        $this->assertSame('INVALID', $result['id_assessment']);
        $this->assertNull($result['date_of_birth']);
    }

    public function test_it_marks_fake_qc_id_when_qc_and_non_qc_markers_are_mixed(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
QUEZON CITY
PHILHEALTH IDENTIFICATION CARD
DATE ISSUED 2024/02/16
VALID UNTIL 2034/10/01
005 000 01257479
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'John Sample');

        $this->assertFalse($result['is_valid']);
        $this->assertSame('Fake QC ID', $result['id_assessment']);
    }

    public function test_it_accepts_imperfect_but_still_valid_qcid_ocr_text(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
KASAMA KA SA PAG UNLAD
MANGUNE, SEAN MICHAEL CALINAWAN
DATE ISSUED 2024/02/16 VALID UNTIL 2034/10/01
19 A KING CONSTANTINE EXT KINGSPOINT BAGBAG, QUEZON CITY
005 000 01257479
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Sean Michael Mangune');

        $this->assertTrue($result['is_valid']);
        $this->assertSame('005 000 01257479', $result['id_number']);
        $this->assertTrue($result['name_matches']);
    }

    public function test_it_accepts_heavily_garbled_real_world_ocr_output(): void
    {
        // Simulates typical Tesseract.js output from a phone camera
        // photo of a physical QC ID with coloured backgrounds:
        $text = <<<'TEXT'
QCITIZENCARD
KASAMA KA SA PAG-UNLAD
Last Name First Name Middle Name
MANGUNE SEAN MICHAEL CALUNAWAN
20030/01 SINGLE
2024/02/16 2034/01/01
19A KING CONSTANTINE EXT
CONSTITUENCY BAGONG QUEZON CITY
IN CASE OF EMERGENCY
005 000 01257419
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text);

        $this->assertTrue($result['is_valid']);
        $this->assertGreaterThanOrEqual(40, $result['confidence_score']);
    }

    public function test_it_accepts_minimal_fragment_ocr_output(): void
    {
        // Even when OCR is quite bad, enough fragments should pass.
        $text = <<<'TEXT'
QCITIZENCARD
QUEZON CITY
MANGUNE, SEAN MICHAEL
DATE ISSUED 2024/02/16
VALID UNTIL 2034/10/01
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text);

        $this->assertTrue($result['is_valid']);
    }

    public function test_fuzzy_name_matching_with_ocr_typos(): void
    {
        $verifier = new QcIdOcrVerifier();

        // Exact match
        $this->assertTrue($verifier->namesLikelyMatch('Sean Mangune', 'MANGUNE, SEAN MICHAEL'));
        // OCR truncated middle name
        $this->assertTrue($verifier->namesLikelyMatch('Sean Michael Mangune', 'MANGUNE SEAN MICHAE'));
        // 1-char OCR typo
        $this->assertTrue($verifier->namesLikelyMatch('Sean Mangune', 'MANGUNE SEAK'));
    }

    public function test_it_prefers_structured_region_hints_for_dates_and_address(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
MANGUNE, SEAN MICHAEL CALINAWAN AN
M 2003/10/01 SINGLE
SEX DATE OF BIRTH CIVIL STATUS
2024/02/16 2034/10/01
DATE ISSUED VALID UNTIL
ADDRESS
19 A KING CONSTANTINE EXT, KINGSPOINT BAGBAG, QUEZON CITY SIGNATURE
005 000 01257479
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Sean Michael Mangune');

        $this->assertSame('MANGUNE, SEAN MICHAEL CALINAWAN', $result['cardholder_name']);
        $this->assertSame('2024/02/16', $result['date_issued']);
        $this->assertSame('2034/10/01', $result['valid_until']);
        $this->assertSame('19 A KING CONSTANTINE EXT, KINGSPOINT BAGBAG, QUEZON CITY', $result['address']);
    }

    public function test_it_keeps_qc_id_numbers_digits_only(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
QUEZON CITY
DATE ISSUED 2024/02/16
VALID UNTIL 2034/10/01
005 000 01257479
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text);

        $this->assertSame('005 000 01257479', $result['id_number']);
    }
}
