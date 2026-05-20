<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'academic_year_id',
        'major_id',
        'pic_user_id',
        'name',
        'school_origin',
        'parent_name',
        'no_hp',
        'visit_date',
        'notes',
        'global_status',
        'follow_up_status',
        'payment_status',
        'last_follow_up_at',
        'paid_at',
        'tested_at',
    ];

    protected $casts = [
        'visit_date'        => 'date',
        'last_follow_up_at' => 'datetime',
        'paid_at'           => 'datetime',
        'tested_at'         => 'datetime',
    ];

    // ============================================
    // KONSTANTA STATUS
    // WHY konstanta? Supaya tidak ada "magic string"
    // di seluruh kodebase. Kalau mau ganti nama status,
    // cukup ubah di sini saja.
    // ============================================

    const GLOBAL_STATUS = [
        'new'        => 'Baru',
        'active'     => 'Aktif',
        'registered' => 'Terdaftar',
        'tested'     => 'Sudah Tes',
        'done'       => 'Selesai',
        'cancelled'  => 'Dibatalkan',
    ];

    const FOLLOW_UP_STATUS = [
        'hot'            => 'Hot 🔥',
        'warm'           => 'Warm ☀️',
        'cold'           => 'Cold ❄️',
        'no_response'    => 'No Response',
        'interested'     => 'Tertarik',
        'not_interested' => 'Tidak Tertarik',
        'closing'        => 'Closing ✅',
    ];

    const PAYMENT_STATUS = [
        'unpaid' => 'Belum Bayar',
        'dp'     => 'DP',
        'paid'   => 'Lunas',
    ];

    // ============================================
    // RELASI
    // ============================================

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function major()
    {
        return $this->belongsTo(Major::class);
    }

    public function picUser()
    {
        // pic = Person In Charge (panitia yang melayani)
        return $this->belongsTo(User::class, 'pic_user_id');
    }

    public function followUps()
    {
        return $this->hasMany(FollowUp::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    public function medicalCheck()
    {
        // hasOne karena satu siswa hanya punya satu pemeriksaan
        return $this->hasOne(MedicalCheck::class);
    }

    public function uniformSize()
    {
        return $this->hasOne(UniformSize::class);
    }

    public function doctorReview()
    {
        return $this->hasOne(DoctorReview::class);
    }

    public function studentSessions()
    {
        return $this->hasMany(StudentSession::class);
    }

    // Ambil sesi aktif saat ini
    public function activeSession()
    {
        return $this->hasOne(StudentSession::class)
            ->where('is_active', true);
    }

    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    // ============================================
    // SCOPE — Filter query yang reusable
    // ============================================

    // filter siswa yang belum dibatalkan
    public function scopeNotCancelled($query)
    {
        return $query->where('global_status', '!=', 'cancelled');
    }

    // filter siswa yang sudah bayar
    public function scopeHasPaid($query)
    {
        return $query->whereIn('payment_status', ['dp', 'status']);
    }

    // Filter siswa yang belum ikut tes
    public function scopeNotTested($query)
    {
        return $query->whereNotIn('global_status', ['tested', 'done']);
    }

    // Filter berdasarkan tahun ajaran aktif
    public function scopeCurrentYear($query)
    {
        return $query->whereHas('academicYear', function ($q) {
            $q->where('is_active', true);
        });
    }

    // ============================================
    // BUSINESS RULE HELPERS
    // ============================================

    public function canJoinTestSession(): bool
    {
        // siswa belum bayar
        if ($this->payment_status === 'unpaid') {
            return false;
        }

        // siswa gajadi
        if ($this->global_status === 'cancelled') {
            return false;
        }

        return true;
    }

    // Apakah siswa sudah dibatalkan?
    public function isCancelled(): bool
    {
        return $this->global_status === 'cancelled';
    }

    // Apakah siswa sudah selesai proses?
    public function isDone(): bool
    {
        return in_array($this->global_status, ['done', 'tested']);
    }

    // ============================================
    // ACCESSOR — Format data saat diambil
    // ============================================

    // format nomor hp 
    public function getFormattedPhoneAttribute(): string
    {
        $phone = $this->no_hp;
        if (strlen($phone) >= 10) {
            return substr($phone, 0, 4) . '-' .
                substr($phone, 4, 4) . '-' .
                substr($phone, 8);
        }
        return $phone;
    }
}
