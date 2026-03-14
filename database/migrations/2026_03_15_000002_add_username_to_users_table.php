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
            $table->string('username')->nullable()->after('name');
        });

        $staffUsers = DB::table('users')
            ->select('id')
            ->whereNull('username')
            ->whereIn('role', ['admin', 'librarian'])
            ->get();

        foreach ($staffUsers as $staffUser) {
            DB::table('users')
                ->where('id', $staffUser->id)
                ->update(['username' => 'staff_' . $staffUser->id]);
        }

        Schema::table('users', function (Blueprint $table) {
            $table->unique('username');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique('users_username_unique');
            $table->dropColumn('username');
        });
    }
};
