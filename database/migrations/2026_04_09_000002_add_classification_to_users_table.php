<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('classification')->default('student')->after('role');
        });

        DB::table('users')
            ->where('role', 'admin')
            ->update(['classification' => 'admin']);

        DB::table('users')
            ->where('role', 'librarian')
            ->update(['classification' => 'faculty']);

        DB::table('users')
            ->whereNotIn('role', ['admin', 'librarian'])
            ->update(['classification' => 'student']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('classification');
        });
    }
};
