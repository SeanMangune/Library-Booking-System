<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('bookings', 'qr_code_path')) {
                $table->string('qr_code_path')->nullable()->after('status');
            }
            if (!Schema::hasColumn('bookings', 'booking_code')) {
                $table->string('booking_code')->unique()->nullable()->after('qr_code_path');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('bookings')) {
            return;
        }

        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'booking_code')) {
                $table->dropUnique(['booking_code']);
                $table->dropColumn('booking_code');
            }
            if (Schema::hasColumn('bookings', 'qr_code_path')) {
                $table->dropColumn('qr_code_path');
            }
        });
    }
};
