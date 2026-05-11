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
        Schema::create('test_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();

            $table->string('wave_name');
            $table->integer('wave_number');
            $table->enum('session_type', ['morning', 'afternoon', 'evening']);
            $table->date('test_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_student');
            $table->text('notes')->nullable();
            $table->enum('status', ['open', 'full', 'ongoing', 'completed', 'canceled'])->default('open');


            $table->timestamps();
            $table->softDeletes();

            $table->index(['school_id', 'academic_year_id']);
            $table->index('wave_name');
            $table->index('test_date');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_sessions');
    }
};
