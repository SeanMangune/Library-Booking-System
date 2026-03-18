<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('booking_status', ['upcoming', 'valid', 'expired'])
                ->default('upcoming')
                ->after('status');
        });

        $this->syncExistingBookingStatuses();
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('booking_status');
        });
    }

    private function syncExistingBookingStatuses(): void
    {
        $now = now();
        $currentDate = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        DB::update(
            <<<'SQL'
UPDATE bookings
SET booking_status = CASE
    WHEN date > ? THEN 'upcoming'
    WHEN date < ? THEN 'expired'
    WHEN start_time IS NOT NULL AND ? < start_time THEN 'upcoming'
    WHEN start_time IS NOT NULL AND end_time IS NOT NULL AND ? >= start_time AND ? <= end_time THEN 'valid'
    WHEN end_time IS NOT NULL AND ? > end_time THEN 'expired'
    ELSE 'upcoming'
END
SQL,
            [$currentDate, $currentDate, $currentTime, $currentTime, $currentTime, $currentTime]
        );
    }
};
