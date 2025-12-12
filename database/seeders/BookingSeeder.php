<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        // Create rooms matching the user's requirements
        $collaborativeA = Room::create([
            'name' => 'Collaborative Room A',
            'slug' => 'collaborative-room-a',
            'capacity' => 10,
            'location' => '2F Library',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Collaborative workspace for group study and projects',
        ]);

        $collaborativeB = Room::create([
            'name' => 'Collaborative Room B',
            'slug' => 'collaborative-room-b',
            'capacity' => 8,
            'location' => '2F Library',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Collaborative workspace for group study and projects',
        ]);

        $collaborativeC = Room::create([
            'name' => 'Collaborative Room C',
            'slug' => 'collaborative-room-c',
            'capacity' => 12,
            'location' => '3F Library',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Collaborative workspace for group study and projects',
        ]);

        $collaborativeD = Room::create([
            'name' => 'Collaborative Room D',
            'slug' => 'collaborative-room-d',
            'capacity' => 6,
            'location' => '3F Library',
            'status' => 'operational',
            'requires_approval' => false,
            'description' => 'Small collaborative workspace',
        ]);

        $collaborativeE = Room::create([
            'name' => 'Collaborative Room E',
            'slug' => 'collaborative-room-e',
            'capacity' => 15,
            'location' => '4F Library',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Large collaborative workspace for team meetings',
        ]);

        $conferenceRoom = Room::create([
            'name' => 'Conference Room',
            'slug' => 'conference-room',
            'capacity' => 20,
            'location' => '4F Executive',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Main conference room for formal meetings',
        ]);

        $libraryRoom = Room::create([
            'name' => 'Library Room',
            'slug' => 'library-room',
            'capacity' => 30,
            'location' => '1F Main',
            'status' => 'operational',
            'requires_approval' => true,
            'description' => 'Main library room for events and large gatherings',
        ]);

        $today = Carbon::today();

        // Today's reservations
        Booking::create([
            'room_id' => $collaborativeA->id,
            'title' => 'Group Study Session',
            'user_name' => 'John Smith',
            'user_email' => 'john.smith@qcu.edu.ph',
            'date' => $today,
            'start_time' => '11:00',
            'end_time' => '12:00',
            'time' => '11:00 AM',
            'duration' => '1 hour',
            'attendees' => 8,
            'status' => 'approved',
            'description' => 'Group study for finals preparation',
        ]);

        Booking::create([
            'room_id' => $collaborativeB->id,
            'title' => 'Project Discussion',
            'user_name' => 'Maria Santos',
            'user_email' => 'maria.santos@qcu.edu.ph',
            'date' => $today,
            'start_time' => '14:00',
            'end_time' => '16:00',
            'time' => '2:00 PM',
            'duration' => '2 hours',
            'attendees' => 5,
            'status' => 'approved',
            'description' => 'Capstone project team meeting',
        ]);

        // Upcoming reservations
        Booking::create([
            'room_id' => $conferenceRoom->id,
            'title' => 'Faculty Meeting',
            'user_name' => 'Juan Dela Cruz',
            'user_email' => 'juan.delacruz@qcu.edu.ph',
            'date' => $today->copy()->addDay(),
            'start_time' => '10:00',
            'end_time' => '12:00',
            'time' => '10:00 AM',
            'duration' => '2 hours',
            'attendees' => 15,
            'status' => 'approved',
            'description' => 'Monthly faculty meeting',
        ]);

        Booking::create([
            'room_id' => $collaborativeC->id,
            'title' => 'Research Discussion',
            'user_name' => 'Anna Reyes',
            'user_email' => 'anna.reyes@qcu.edu.ph',
            'date' => $today->copy()->addDays(2),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'time' => '10:00 AM',
            'duration' => '1 hour',
            'attendees' => 8,
            'status' => 'approved',
            'description' => 'Research methodology discussion',
        ]);

        // Pending bookings for approvals
        Booking::create([
            'room_id' => $collaborativeD->id,
            'title' => 'Study Group',
            'user_name' => 'Sarah Johnson',
            'user_email' => 'sarah.j@qcu.edu.ph',
            'date' => $today,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'time' => '10:00 AM',
            'duration' => '1 hour',
            'attendees' => 6,
            'status' => 'pending',
        ]);

        Booking::create([
            'room_id' => $libraryRoom->id,
            'title' => 'Book Club Meeting',
            'user_name' => 'Carlos Garcia',
            'user_email' => 'carlos.g@qcu.edu.ph',
            'date' => $today->copy()->addDay(),
            'start_time' => '15:00',
            'end_time' => '17:00',
            'time' => '3:00 PM',
            'duration' => '2 hours',
            'attendees' => 20,
            'status' => 'pending',
        ]);

        Booking::create([
            'room_id' => $collaborativeE->id,
            'title' => 'Thesis Defense Practice',
            'user_name' => 'Michelle Tan',
            'user_email' => 'michelle.t@qcu.edu.ph',
            'date' => $today->copy()->addDays(3),
            'start_time' => '09:00',
            'end_time' => '12:00',
            'time' => '9:00 AM',
            'duration' => '3 hours',
            'attendees' => 10,
            'status' => 'pending',
        ]);

        // Past approved booking
        Booking::create([
            'room_id' => $conferenceRoom->id,
            'title' => 'Department Meeting',
            'user_name' => 'Juan Dela Cruz',
            'user_email' => 'juan.delacruz@qcu.edu.ph',
            'date' => $today->copy()->subDay(),
            'start_time' => '11:00',
            'end_time' => '14:00',
            'time' => '11:00 AM',
            'duration' => '3 hours',
            'attendees' => 18,
            'status' => 'approved',
        ]);

        // Some rejected and cancelled bookings
        Booking::create([
            'room_id' => $collaborativeA->id,
            'title' => 'Private Tutoring',
            'user_name' => 'Mike Wilson',
            'user_email' => 'mike.w@qcu.edu.ph',
            'date' => $today,
            'start_time' => '10:00',
            'end_time' => '11:00',
            'time' => '10:00 AM',
            'duration' => '1 hour',
            'attendees' => 2,
            'status' => 'cancelled',
        ]);

        Booking::create([
            'room_id' => $collaborativeB->id,
            'title' => 'Party Event',
            'user_name' => 'Emma Brown',
            'user_email' => 'emma.b@qcu.edu.ph',
            'date' => $today,
            'start_time' => '18:00',
            'end_time' => '22:00',
            'time' => '6:00 PM',
            'duration' => '4 hours',
            'attendees' => 25,
            'status' => 'rejected',
        ]);

        Booking::create([
            'room_id' => $libraryRoom->id,
            'title' => 'Cancelled Event',
            'user_name' => 'Test User',
            'user_email' => 'test@qcu.edu.ph',
            'date' => $today->copy()->addDay(),
            'start_time' => '10:00',
            'end_time' => '16:00',
            'time' => '10:00 AM',
            'duration' => '6 hours',
            'attendees' => 25,
            'status' => 'cancelled',
        ]);
    }
}
