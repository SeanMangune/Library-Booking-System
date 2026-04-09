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
            $table->enum('room_status', ['available', 'occupied', 'maintenance'])->default('available')->after('status');
        });

        DB::table('bookings')
            ->update(['room_status' => 'available']);
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('room_status');
        });
    }
};
