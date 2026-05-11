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
        Schema::create('doctor_reviews', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('doctor_user_id')   // dokter yang mewawancara
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('final_major_id')   // jurusan final hasil rekomendasi dokter
                ->nullable()
                ->constrained('majors')
                ->nullOnDelete();

            $table->enum('color_blind_result', [
                'normal',           // tidak buta warna
                'blind',            // buta warna 
            ])->nullable();

            // Kelulusan
            $table->enum('result', [
                'passed',           // lulus
                'failed',           // tidak lulus
                'pending',          // masih dalam pertimbangan
            ])->default('pending');

            $table->text('doctor_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            // Satu siswa hanya punya satu hasil wawancara dokter
            $table->unique('student_id');
            $table->index('doctor_user_id');
            $table->index('result');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctor_reviews');
    }
};
