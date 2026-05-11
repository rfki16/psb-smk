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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['kk', 'akte', 'ktp_parent', 'photo_2x3', 'photo_3x4', 'photo_4x6', 'rekening_kjp']);
            $table->boolean('is_collected')->default(false);
            $table->string('file_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->timestamps();

            // satu siswa hanya punya satu jenis record document
            $table->unique(['student_id', 'type']);
            $table->index('student_id');
            $table->index('is_collected');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
