<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (! Schema::hasColumn('bookings', 'decision_by_user_id')) {
                $table->unsignedBigInteger('decision_by_user_id')->nullable();
            }

            if (! Schema::hasColumn('bookings', 'decision_by_name')) {
                $table->string('decision_by_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            if (Schema::hasColumn('bookings', 'decision_by_name')) {
                $table->dropColumn('decision_by_name');
            }

            if (Schema::hasColumn('bookings', 'decision_by_user_id')) {
                $table->dropColumn('decision_by_user_id');
            }
        });
    }
};
