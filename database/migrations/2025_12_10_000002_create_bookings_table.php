<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('user_name');
            $table->string('user_email');
            $table->date('date');
            $table->string('time');
            $table->string('duration');
            $table->integer('attendees')->default(1);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->boolean('has_conflict')->default(false);
            $table->unsignedBigInteger('conflicts_with')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->foreign('conflicts_with')->references('id')->on('bookings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
