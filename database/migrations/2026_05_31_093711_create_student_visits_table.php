<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            $table->integer('visit_number');
            $table->date('visit_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'visit_number']);
            $table->index('student_id');
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_visits');
    }
};
