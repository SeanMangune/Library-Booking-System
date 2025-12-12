<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new fields to rooms table
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('location')->nullable()->after('capacity');
            $table->enum('status', ['operational', 'maintenance', 'closed'])->default('operational')->after('location');
            $table->boolean('requires_approval')->default(false)->after('status');
            $table->timestamp('status_start_at')->nullable()->after('requires_approval');
            $table->timestamp('status_end_at')->nullable()->after('status_start_at');
        });

        // Add new fields to bookings table
        Schema::table('bookings', function (Blueprint $table) {
            $table->string('title')->nullable()->after('room_id');
            $table->text('description')->nullable()->after('title');
            $table->foreignId('user_id')->nullable()->after('description');
            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->change();
        });
    }

    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['location', 'status', 'requires_approval', 'status_start_at', 'status_end_at']);
        });

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['title', 'description', 'user_id', 'start_time', 'end_time']);
        });
    }
};
