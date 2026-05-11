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
        Schema::create('follow_ups', function (Blueprint $table) {
            $table->id();

            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['hot', 'warm', 'cold', 'no_response', 'interested', 'not_interested', 'closing']);
            $table->text('notes');
            $table->date('follow_up_date');
            $table->enum('method', ['whatsapp', 'phone', 'in_person', 'other'])->default('whatsapp');
            $table->timestamps();

            // indexing
            $table->index('student_id');
            $table->index('user_id');
            $table->index('follow_up_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('follow_ups');
    }
};
