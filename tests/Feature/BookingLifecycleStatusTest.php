<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookingLifecycleStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_future_booking_is_saved_as_upcoming(): void
    {
        Carbon::setTestNow('2026-03-18 10:00:00');

        $booking = $this->createBooking([
            'date' => '2027-01-01',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->assertSame('upcoming', $booking->booking_status);
    }

    public function test_today_booking_inside_time_window_is_saved_as_valid(): void
    {
        Carbon::setTestNow('2026-03-18 09:30:00');

        $booking = $this->createBooking([
            'date' => '2026-03-18',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->assertSame('valid', $booking->booking_status);
    }

    public function test_today_booking_past_end_time_becomes_expired_on_retrieval(): void
    {
        Carbon::setTestNow('2026-03-18 08:30:00');

        $booking = $this->createBooking([
            'date' => '2026-03-18',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $this->assertSame('upcoming', $booking->booking_status);

        Carbon::setTestNow('2026-03-18 10:30:00');

        $freshBooking = Booking::query()->findOrFail($booking->id);

        $this->assertSame('expired', $freshBooking->booking_status);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'booking_status' => 'expired',
        ]);
    }

    private function createBooking(array $overrides = []): Booking
    {
        $room = Room::create([
            'name' => 'Status Test Room',
            'slug' => 'status-test-room',
            'capacity' => 10,
            'status' => 'operational',
            'requires_approval' => false,
        ]);

        return Booking::create(array_merge([
            'room_id' => $room->id,
            'title' => 'Lifecycle Status Test',
            'user_name' => 'Test User',
            'user_email' => 'test@example.com',
            'date' => '2026-03-18',
            'time' => '9:00 AM',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'duration' => '1h',
            'attendees' => 1,
            'status' => 'pending',
        ], $overrides));
    }
}
