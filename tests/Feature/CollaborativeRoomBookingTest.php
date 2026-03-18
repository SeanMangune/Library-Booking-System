<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Services\QcIdOcrVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CollaborativeRoomBookingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_student_can_request_twelve_attendees_in_a_collaborative_room_for_staff_approval(): void
    {
        /** @var User $student */
        $student = User::factory()->create([
            'name' => 'Student User',
            'role' => User::ROLE_USER,
        ]);

        $room = Room::create([
            'name' => 'Collaborative Room C',
            'slug' => 'collaborative-room-c',
            'capacity' => 10,
            'location' => 'Third Floor',
            'status' => 'operational',
            'requires_approval' => false,
        ]);

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

        $room = Room::create([
            'name' => 'Collaborative Room C',
            'slug' => 'collaborative-room-c',
            'capacity' => 10,
            'location' => 'Third Floor',
            'status' => 'operational',
            'requires_approval' => false,
        ]);

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
            'message' => 'Collaborative rooms are fixed at 10 attendees, and can only be extended up to 12 with librarian permission.',
        ]);
    }

    public function test_librarian_must_add_an_approval_note_when_granting_extra_collaborative_capacity(): void
    {
        /** @var User $student */
        $student = User::factory()->create([
            'role' => User::ROLE_USER,
        ]);
        /** @var User $librarian */
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
        ]);

        $room = Room::create([
            'name' => 'Collaborative Room C',
            'slug' => 'collaborative-room-c',
            'capacity' => 10,
            'location' => 'Third Floor',
            'status' => 'operational',
            'requires_approval' => false,
        ]);

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

        $missingNoteResponse = $this->actingAs($librarian)->postJson(route('approvals.approve', $booking), []);

        $missingNoteResponse->assertStatus(422);
        $missingNoteResponse->assertJsonFragment([
            'message' => 'Add a short approval note before granting a collaborative room booking above 10 attendees.',
        ]);

        $approvedResponse = $this->actingAs($librarian)->postJson(route('approvals.approve', $booking), [
            'reason' => 'Approved after confirming the group size and room availability.',
        ]);

        $approvedResponse->assertOk();
        $approvedResponse->assertJsonPath('booking.booking_status', 'upcoming');
        $approvedResponse->assertJsonPath('booking.qr_status', 'upcoming');
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'approved',
            'booking_status' => 'upcoming',
        ]);
    }
}
