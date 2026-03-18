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
        $this->assertNull($result['date_of_birth']);
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

    public function test_it_prefers_bottom_strip_id_and_filters_noisy_address_content(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
KASAMA KA SA PAG UNLAD
LAST NAME FIRST NAME MIDDLE NAME
A JIBE MICCO JIRO FRUELDA
SEX DATE OF BIRTH CIVIL STATUS
M 2000/08/25 SINGLE
DATE ISSUED VALID UNTIL
ADDRESS 3 SN P HA ET O HY AR O I A 3 REPUBLIC OF THE QR OPMN AT ST ES AE CS R 2D PS AX QE ZENCARIDES UL 220 IA GXTYSAMA KA SA PAG UNLAD SN O ERE EI AAA LAST NAME FIRST NAME MIDDLE NAME A JIBE MICCO JIRO FRUELDA QUEZON CITY
123 000 09744557
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Micco Jiro Fruelda');

        $this->assertSame('123 000 09744557', $result['id_number']);
        $this->assertStringContainsString('QUEZON CITY', (string) $result['address']);
        $this->assertStringNotContainsString('LAST NAME', (string) $result['address']);
    }

    public function test_it_extracts_structured_fields_from_qc_id_layout_like_uploaded_card(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
KASAMA KA SA PAG UNLAD
LAST NAME, FIRST NAME, MIDDLE NAME
A JIBE, MICCO JIRO FRUELDA
M 2003/08/25 SINGLE
SEX DATE OF BIRTH CIVIL STATUS
DATE ISSUED 2022/12/05
VALID UNTIL 2032/08/25
ADDRESS BLK-2 LOT-23 SANTAN STREET, MALIGAYA PARK SUBD. FAIRVIEW QUEZON CITY, PASONG PUTIK, QUEZON CITY
083 000 00892557
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Micco Jiro Fruelda');

        $this->assertTrue($result['is_valid']);
        $this->assertSame('JIBE, MICCO JIRO FRUELDA', $result['cardholder_name']);
        $this->assertSame('2003/08/25', $result['date_of_birth']);
        $this->assertSame('2022/12/05', $result['date_issued']);
        $this->assertSame('2032/08/25', $result['valid_until']);
        $this->assertSame('083 000 00892557', $result['id_number']);
        $this->assertStringContainsString('BLK-2 LOT-23 SANTAN STREET', (string) $result['address']);
    }

    public function test_it_extracts_blood_type_and_compact_issued_dates_from_noisy_card_text(): void
    {
        $text = <<<'TEXT'
Q CITIZENCARD
KASAMA KA SA PAG UNLAD
LAST NAME, FIRST NAME, MIDDLE NAME
A JIBE, MICCO JIRO FRUELDA
M 2003/08/25 SINGLE
SEX DATE OF BIRTH CIVIL STATUS
O+ 2022/1205 2032/0825
DATE ISSUED VALID UNTIL
ADDRESS 2D PS AX QE ZENCARIDES UL - 220 IA, GXTYSAMA KA SA PAG-UNLAD SN O ERE EI AAA, BLK-2 LOT-23 SANTAN STREET. LIAN VL 3H JIA YA PARK SUBD. FAIRVIEW XA I T PA 2 IIINERIERSIANSIURE QUEZON CITY
083 000 00892557
TEXT;

        $result = (new QcIdOcrVerifier())->verify($text, 'Micco Jiro Fruelda');

        $this->assertTrue($result['is_valid']);
        $this->assertSame('O+', $result['blood_type']);
        $this->assertSame('2022/12/05', $result['date_issued']);
        $this->assertSame('2032/08/25', $result['valid_until']);
        $this->assertSame('083 000 00892557', $result['id_number']);
        $this->assertStringContainsString('BLK-2 LOT-23 SANTAN STREET', (string) $result['address']);
        $this->assertStringNotContainsString('LAST NAME', (string) $result['address']);
    }
}
