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
        Schema::create('students', function (Blueprint $table) {
            $table->id();

            // RELASI
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->nullable()->constrained('majors')->nullOnDelete();
            $table->foreignId('user_pic_id')->nullable()->constrained('users')->nullOnDelete();

            // DATA PRIBADI
            $table->string('name');
            $table->string('school_origin');
            $table->string('parent_name');
            $table->string('no_hp');
            $table->date('visit_date');
            $table->text('notes')->nullable();

            // MULTI STATUS SYSTEM
            $table->enum('global_status', [
                'new',              // baru daftar
                'active',           // sedang follow up
                'registered',       // sudah bayar 
                'tested',           // sudah tes kesehatan
                'done',             // lulus
                'cancelled'         // batal
            ])->default('new');

            $table->enum('follow_up_status', [
                'hot',              // sangat berminat, kemungkinan daftar tinggi
                'warm',             // berminat tapi masih ragu
                'cold',             // kurang berminat
                'no_response',      // tidak ada respon saat dihubungi
                'interested',       // tertarik setelah follow up
                'not_interested',   // tidak tertarik setelah follow up 
                'closing'
            ])->nullable();

            $table->enum('payment_status', [
                'unpaid',
                'dp',
                'paid',
            ])->default('unpaid');

            // TIMESTAMP TRACKING
            $table->timestamp('last_follow_up_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('tested_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // INDEXING UNTUK FILTER ATAU SEARCH
            $table->index(['school_id', 'academic_year_id', 'no_hp']);
            $table->index('school_id');
            $table->index('academic_year_id');
            $table->index('global_status');
            $table->index('payment_status');
            $table->index('follow_up_status');
            $table->index('visit_date');
            $table->index('user_pic_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
