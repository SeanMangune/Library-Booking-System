<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminStaffAndReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_admin_can_create_a_librarian_account_from_settings(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($admin)->post(route('settings.staff.store'), [
            'name' => 'Library Staff',
            'username' => 'library_staff',
            'email' => 'librarian@example.com',
            'role' => User::ROLE_LIBRARIAN,
            'password' => 'secure-pass-123',
            'password_confirmation' => 'secure-pass-123',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('users', [
            'username' => 'library_staff',
            'email' => 'librarian@example.com',
            'role' => User::ROLE_LIBRARIAN,
        ]);
    }

    public function test_staff_login_accepts_librarian_accounts(): void
    {
        /** @var User $librarian */
        $librarian = User::factory()->create([
            'role' => User::ROLE_LIBRARIAN,
            'username' => 'lib_account',
            'password' => Hash::make('secret-pass-123'),
        ]);

        $response = $this->post(route('admin.login'), [
            'staff_username' => 'lib_account',
            'staff_password' => 'secret-pass-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($librarian);
    }

    public function test_staff_accounts_can_use_the_unified_login_form(): void
    {
        /** @var User $staff */
        $staff = User::factory()->create([
            'role' => User::ROLE_ADMIN,
            'password' => Hash::make('admin-pass-123'),
        ]);

        $response = $this->post(route('login.post'), [
            'login' => $staff->email,
            'password' => 'admin-pass-123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($staff);
    }

    public function test_librarian_can_open_the_reports_page(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $room = Room::create([
            'name' => 'Collaborative Room A',
            'slug' => 'collaborative-room-a',
            'capacity' => 10,
            'location' => 'Second Floor',
            'status' => 'operational',
            'requires_approval' => true,
        ]);

        Booking::withoutEvents(function () use ($room) {
            Booking::create([
                'room_id' => $room->id,
                'title' => 'Group Study Session',
                'user_name' => 'Student User',
                'user_email' => 'student@example.com',
                'date' => now()->toDateString(),
                'time' => '9:00 AM',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'duration' => '1h',
                'attendees' => 10,
                'status' => 'pending',
            ]);
        });

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertOk();
        $response->assertSee('Detailed Reports');
        $response->assertViewHas('stats', fn (array $stats) => $stats['total'] === 1 && $stats['pending'] === 1);
        $response->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 1);
    }

    public function test_reports_default_filters_include_all_booking_dates(): void
    {
        /** @var User $admin */
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $room = Room::create([
            'name' => 'Collaborative Room B',
            'slug' => 'collaborative-room-b',
            'capacity' => 10,
            'location' => 'Third Floor',
            'status' => 'operational',
            'requires_approval' => true,
        ]);

        Booking::withoutEvents(function () use ($room) {
            Booking::create([
                'room_id' => $room->id,
                'title' => 'Older Booking',
                'user_name' => 'Student One',
                'user_email' => 'student1@example.com',
                'date' => '2025-12-15',
                'time' => '9:00 AM',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'duration' => '1h',
                'attendees' => 8,
                'status' => 'approved',
            ]);

            Booking::create([
                'room_id' => $room->id,
                'title' => 'Newer Booking',
                'user_name' => 'Student Two',
                'user_email' => 'student2@example.com',
                'date' => '2026-03-20',
                'time' => '10:00 AM',
                'start_time' => '10:00',
                'end_time' => '11:00',
                'duration' => '1h',
                'attendees' => 9,
                'status' => 'pending',
            ]);
        });

        $response = $this->actingAs($admin)->get(route('reports.index'));

        $response->assertOk();
        $response->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2 && $stats['approved'] === 1 && $stats['pending'] === 1);
        $response->assertViewHas('bookings', fn ($bookings) => $bookings->total() === 2);
    }
}