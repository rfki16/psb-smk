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
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->nullable()                    // nullable: notif ke grup tidak terkait siswa tertentu
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('sent_by')          // user yang trigger pengiriman
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('type', ['invitation', 'reminder', 'registration_confirmation', 'result', 'general']);

            $table->enum('channel', ['whatsapp'])->default('whatsapp');
            $table->string('recipient_phone');
            $table->enum('status', [
                'pending',   // antri di queue, belum dikirim
                'sent',      // berhasil dikirim
                'failed',    // gagal dikirim
            ])->default('pending');

            $table->json('payload'); // isi pesan lengkap
            $table->timestamp('sent_at')->nullable();
            $table->text('error_message')->nullable(); // pesan error kalau gagal
            $table->timestamps();

            $table->index('student_id');
            $table->index('status');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
