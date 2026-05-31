<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'student_id',
        'user_id',
        'amount',
        'type',
        'method',
        'reference_number',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'amount'       => 'integer',
        'payment_date' => 'date',
    ];

    // ── Relasi ──────────────────────────────────────────

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helper ──────────────────────────────────────────

    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'dp'    => 'DP',
            'lunas' => 'Lunas',
            default => '-',
        };
    }

    public function getMethodLabelAttribute(): string
    {
        return match ($this->method) {
            'tunai'    => 'Tunai',
            'transfer' => 'Transfer',
            default    => '-',
        };
    }
}
