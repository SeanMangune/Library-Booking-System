<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'otp_code')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('users', 'otp_code')) {
            return;
        }

        // Prevent down-migration failure from long hashed values.
        DB::table('users')->whereNotNull('otp_code')->update(['otp_code' => null]);

        Schema::table('users', function (Blueprint $table) {
            $table->string('otp_code', 10)->nullable()->change();
        });
    }
};
