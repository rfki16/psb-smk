<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_pics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Satu PIC tidak boleh duplikat per siswa
            $table->unique(['student_id', 'user_id']);

            // Index untuk query "tampilkan siswa by PIC"
            $table->index('student_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_pics');
    }
};
