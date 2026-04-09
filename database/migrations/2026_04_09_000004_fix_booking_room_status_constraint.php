<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('alter table bookings drop constraint if exists bookings_room_status_check');
        DB::statement("alter table bookings add constraint bookings_room_status_check check (room_status in ('available','occupied','maintenance'))");

        DB::table('bookings')
            ->where('room_status', 'not_occupied')
            ->update(['room_status' => 'available']);
    }

    public function down(): void
    {
        DB::statement('alter table bookings drop constraint if exists bookings_room_status_check');
        DB::statement("alter table bookings add constraint bookings_room_status_check check (room_status in ('not_occupied','occupied','maintenance'))");
    }
};
