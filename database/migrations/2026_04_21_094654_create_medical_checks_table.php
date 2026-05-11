<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('medical_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('checked_by')       // petugas tim kesehatan
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            // Pemeriksaan fisik dasar
            $table->string('blood_pressure')->nullable();  // "120/80" (string karena format sistolik/diastolik)
            $table->decimal('height', 5, 2)->nullable();   // tinggi dalam cm, contoh: 165.50
            $table->decimal('weight', 5, 2)->nullable();   // berat dalam kg, contoh: 55.30

            // BMI bisa dihitung otomatis, tapi kita simpan juga untuk query cepat
            $table->decimal('bmi', 5, 2)->nullable();      // dihitung: weight / (height/100)^2

            $table->text('notes')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            // Satu siswa hanya punya satu pemeriksaan fisik
            $table->unique('student_id');
            $table->index('checked_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_checks');
    }
};
