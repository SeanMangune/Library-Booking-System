<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qc_id_registrations', function (Blueprint $table) {
            $table->string('user_type')->nullable()->after('contact_number');
            $table->string('employee_category')->nullable()->after('user_type');
            $table->string('course')->nullable()->after('employee_category');
        });
    }

    public function down(): void
    {
        Schema::table('qc_id_registrations', function (Blueprint $table) {
            $table->dropColumn(['user_type', 'employee_category', 'course']);
        });
    }
};
