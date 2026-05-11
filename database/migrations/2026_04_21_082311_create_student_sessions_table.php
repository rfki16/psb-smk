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
        Schema::create('student_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('test_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete();

            // is_active = true berarti ini sesi AKTIF siswa saat ini
            // Satu siswa hanya boleh punya SATU is_active = true
            // Kalau pindah sesi: record lama is_active = false, buat record baru is_active = true
            $table->boolean('is_active')->default(true);

            $table->enum('status', ['scheduled', 'attended', 'absent', 'moved'])->default('scheduled');
            $table->text('notes')->nullable(); // alasan pindah
            $table->timestamp('moved_at')->nullable();
            $table->timestamps();

            // satu siswa hanya boleh ada satu di satu sesi
            $table->unique(['student_id', 'test_session_id']);

            $table->index(['student_id', 'is_active']);
            $table->index('test_session_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_sessions');
    }
};
