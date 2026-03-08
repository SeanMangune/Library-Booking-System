<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class QcIdRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_open_qcid_registration_page(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('qcid.registration.show'));

        $response->assertOk();
        $response->assertSee('Register your QC ID');
    }

    public function test_authenticated_user_can_submit_valid_qcid_registration(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wn0J6sAAAAASUVORK5CYII=');

        $ocrText = <<<'TEXT'
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

        $response = $this->actingAs($user)->post(route('qcid.registration.store'), [
            'full_name' => 'Sean Michael Mangune',
            'email' => $user->email,
            'contact_number' => '09123456789',
            'qcid_number' => '005 000 01257479',
            'sex' => 'M',
            'civil_status' => 'SINGLE',
            'date_of_birth' => '2003-10-01',
            'date_issued' => '2024-02-16',
            'valid_until' => '2034-10-01',
            'address' => '19 A KING CONSTANTINE EXT KINGSPOINT BAGBAG, QUEZON CITY',
            'ocr_text' => $ocrText,
            'qcid_image' => UploadedFile::fake()->createWithContent('qcid.png', $png),
        ]);

        $response->assertRedirect(route('qcid.registration.show'));
        $this->assertDatabaseHas('qc_id_registrations', [
            'user_id' => $user->id,
            'verification_status' => 'pending',
        ]);

        $storedPath = (string) \App\Models\QcIdRegistration::query()
            ->where('user_id', $user->id)
            ->value('qcid_image_path');

        Storage::disk('public')->assertExists($storedPath);
    }
}
