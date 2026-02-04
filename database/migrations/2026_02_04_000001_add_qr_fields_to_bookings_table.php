<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'booking_code')) {
                $table->string('booking_code', 32)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('bookings', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('booking_code');
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'qr_code_path')) $table->dropColumn('qr_code_path');
            if (Schema::hasColumn('bookings', 'booking_code')) $table->dropUnique(['booking_code']);
            if (Schema::hasColumn('bookings', 'booking_code')) $table->dropColumn('booking_code');
        });
    }
};
