<?php

namespace Tests\Unit;

use App\Http\Controllers\QcIdVerificationController;
use App\Services\QcIdOcrVerifier;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class QcIdVerificationControllerDobFallbackTest extends TestCase
{
    private function runDobValidation(array &$verification, string $combinedText): ?string
    {
        $controller = new QcIdVerificationController();
        $verifier = new QcIdOcrVerifier();

        $method = new ReflectionMethod($controller, 'dateOfBirthValidationError');
        $method->setAccessible(true);

        $args = [&$verification, $combinedText, $verifier];

        /** @var string|null $result */
        $result = $method->invokeArgs($controller, $args);

        return $result;
    }

    public function test_it_recovers_dob_from_dot_separated_birth_value(): void
    {
        $verification = [
            'date_of_birth' => null,
            'date_issued' => '2024.02.16',
            'valid_until' => '2034.10.01',
            'normalized_text' => 'Q CITIZENCARD DATE OF BIRTH 2003.10.01 DATE ISSUED 2024.02.16 VALID UNTIL 2034.10.01',
        ];

        $error = $this->runDobValidation($verification, (string) $verification['normalized_text']);

        $this->assertNull($error);
        $this->assertSame('2003-10-01', $verification['date_of_birth']);
        $this->assertSame('ocr_fallback', $verification['_date_of_birth_source']);
    }

    public function test_it_rejects_when_only_issue_and_validity_dates_are_present(): void
    {
        $verification = [
            'date_of_birth' => null,
            'date_issued' => '2024/02/16',
            'valid_until' => '2034/10/01',
            'normalized_text' => 'Q CITIZENCARD DATE ISSUED 2024/02/16 VALID UNTIL 2034/10/01',
        ];

        $error = $this->runDobValidation($verification, (string) $verification['normalized_text']);

        $this->assertSame('Date of birth could not be extracted from your QC ID. Please upload a clearer image and try again.', $error);
    }
}
