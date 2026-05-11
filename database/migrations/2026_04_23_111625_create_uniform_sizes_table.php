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
        Schema::create('uniform_sizes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('measured_by')      // petugas seragam
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->enum('size_options', ['S', 'M', 'L', 'XL', '2L', '3L', '4L', '6L']);

            $table->enum('profession_size', ['S', 'M', 'L', 'XL', '2L', '3L', '4L', '6L'])->nullable();
            $table->enum('batik_size', ['S', 'M', 'L', 'XL', '2L', '3L', '4L', '6L'])->nullable();
            $table->enum('sport_size', ['S', 'M', 'L', 'XL', '2L', '3L', '4L', '6L'])->nullable();
            $table->enum('almamater', ['S', 'M', 'L', 'XL', '2L', '3L', '4L', '6L'])->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('measured_at')->nullable();

            $table->timestamps();

            // Satu siswa hanya punya satu data ukuran seragam
            $table->unique('student_id');
            $table->index('measured_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('uniform_sizes');
    }
};
