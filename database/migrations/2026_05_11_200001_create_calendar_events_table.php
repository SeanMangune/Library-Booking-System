<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('type'); // holiday, online_class, school_event, custom
            $table->date('date');
            $table->time('start_time')->nullable(); // null = all-day event
            $table->time('end_time')->nullable();
            $table->boolean('is_all_day')->default(true);
            $table->string('color', 20)->nullable(); // custom color override
            $table->string('source')->default('manual'); // manual, api (auto-synced holidays)
            $table->string('source_id')->nullable(); // external API reference
            $table->boolean('is_recurring')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['date', 'type']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
