<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\QcIdOcrVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Tests\TestCase;

class CollaborativeRoomBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_student_can_request_twelve_attendees_in_a_collaborative_room_for_staff_approval(): void
    {
        /** @var User $student */
        $student = User::factory()->create([
            'name' => 'Student User',
            'role' => User::ROLE_USER,
        ]);

        $room = $this->collaborativeRoom();

        $this->mock(QcIdOcrVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->andReturn([
                'is_valid' => true,
                'name_matches' => true,
                'cardholder_name' => 'Student User',
                'confidence_score' => 97,
            ]);
        });

        $response = $this->actingAs($student)->postJson(route('reservations.store'), [
            'purpose' => 'Research Discussion',
            'room_id' => $room->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'attendees' => 12,
            'user_name' => 'Student User',
            'user_email' => 'student@example.com',
            'qc_id_ocr_text' => str_repeat('QUEZON CITY CITIZEN CARD ', 3),
        ]);

        $response->assertOk();
        $response->assertJsonPath('booking.status', 'pending');
        $response->assertJsonPath('booking.title', 'Research Discussion');
    }

    public function test_student_cannot_request_more_than_twelve_attendees_in_a_collaborative_room(): void
    {
        /** @var User $student */
        $student = User::factory()->create([
            'name' => 'Student User',
            'role' => User::ROLE_USER,
        ]);

        $room = $this->collaborativeRoom();

        $this->mock(QcIdOcrVerifier::class, function ($mock) {
            $mock->shouldReceive('verify')->never();
        });

        $response = $this->actingAs($student)->postJson(route('reservations.store'), [
            'purpose' => 'Research Discussion',
            'room_id' => $room->id,
            'date' => now()->addDay()->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'attendees' => 13,
            'user_name' => 'Student User',
            'user_email' => 'student@example.com',
            'qc_id_ocr_text' => str_repeat('QUEZON CITY CITIZEN CARD ', 3),
        ]);

        $response->assertStatus(422);
        $response->assertJsonFragment([
            'message' => 'Collaborative rooms allow up to 10 attendees by default, and can only be extended up to 12 with librarian permission.',
        ]);
    }

    public function test_admin_can_approve_extra_collaborative_capacity_without_an_approval_note(): void
    {
        /** @var User $student */
        $student = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $room = $this->collaborativeRoom();

        $booking = Booking::withoutEvents(function () use ($room, $student) {
            return Booking::create([
                'room_id' => $room->id,
                'title' => 'Research Discussion',
                'user_id' => $student->id,
                'user_name' => $student->name,
                'user_email' => $student->email,
                'date' => now()->addDay()->toDateString(),
                'time' => '9:00 AM',
                'start_time' => '09:00',
                'end_time' => '11:00',
                'duration' => '2h',
                'attendees' => 12,
                'status' => 'pending',
            ]);
        });

        $approvedResponse = $this->actingAs($admin)->postJson(route('approvals.approve', $booking), []);

        $approvedResponse->assertOk();
        $approvedResponse->assertJsonPath('booking.booking_status', 'upcoming');
        $approvedResponse->assertJsonPath('booking.qr_status', 'upcoming');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'approved',
            'booking_status' => 'upcoming',
            'reason' => null,
        ]);
    }

    private function collaborativeRoom(): Room
    {
        return Room::query()->firstOrCreate(
            ['slug' => 'collaborative-room-c'],
            [
                'name' => 'Collaborative Room C',
                'capacity' => 10,
                'location' => 'Third Floor',
                'status' => 'operational',
                'requires_approval' => true,
            ]
        );
    }
}
