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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('school_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('key'); // max student
            $table->text('value')->nullable(); // nilai setting
            $table->enum('type', [
                'string',
                'integer',
                'boolean',
                'json',
            ])->default('string');

            $table->string('description')->nullable();
            $table->timestamps();

            // Satu sekolah tidak boleh punya dua setting dengan key yang sama
            $table->unique(['school_id', 'key']);
            $table->index('school_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
