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
            $table->string('qr_validity', 20)->default('not_valid')->after('booking_status');
        });

        $this->syncExistingQrValidity();
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('qr_validity');
        });
    }

    /**
     * Set qr_validity for all existing rows:
     *  - 'valid'     when status = 'approved' AND date = today AND now() is between start_time and end_time
     *  - 'not_valid' for everything else (pending, cancelled, wrong date, outside time window)
     */
    private function syncExistingQrValidity(): void
    {
        $now         = now();
        $currentDate = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        DB::update(
            <<<'SQL'
UPDATE bookings
SET qr_validity = CASE
    WHEN status = 'approved'
         AND date = ?
         AND start_time IS NOT NULL
         AND end_time IS NOT NULL
         AND ? >= start_time
         AND ? <= end_time
    THEN 'valid'
    ELSE 'not_valid'
END
SQL,
            [$currentDate, $currentTime, $currentTime]
        );
    }
};
