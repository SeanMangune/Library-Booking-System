<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_id_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->unique();
            $table->string('full_name');
            $table->string('email');
            $table->string('contact_number')->nullable();
            $table->string('qcid_number')->nullable();
            $table->string('sex')->nullable();
            $table->string('civil_status')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->date('date_issued')->nullable();
            $table->date('valid_until')->nullable();
            $table->text('address')->nullable();
            $table->longText('ocr_text');
            $table->string('verification_status')->default('pending');
            $table->text('verification_notes')->nullable();
            $table->string('qcid_image_path');
            $table->json('verified_data')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_id_registrations');
    }
};
