<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'status',
        'notes',
        'follow_up_date',
        'method',
    ];

    protected $casts = [
        'follow_up_date' => 'date',
    ];

    // ============================================
    // KONSTANTA
    // ============================================

    const STATUS = [
        'hot'            => 'Hot 🔥',
        'warm'           => 'Warm ☀️',
        'cold'           => 'Cold ❄️',
        'no_response'    => 'No Response',
        'interested'     => 'Tertarik',
        'not_interested' => 'Tidak Tertarik',
        'closing'        => 'Closing ✅',
    ];

    const METHOD = [
        'whatsapp'  => 'WhatsApp',
        'phone'     => 'Telepon',
        'in_person' => 'Tatap Muka',
        'other'     => 'Lainnya',
    ];

    // ============================================
    // RELASI
    // ============================================

    // Follow up ini milik siswa mana
    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    // Follow up ini dilakukan oleh user mana
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // ============================================
    // SCOPE
    // ============================================

    // Filter follow up hari ini
    public function scopeToday($query)
    {
        return $query->whereDate('follow_up_date', today());
    }

    // Filter follow up minggu ini
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('follow_up_date', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }
}
