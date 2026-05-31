<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPic extends Model
{
    protected $fillable = [
        'student_id',
        'user_id',
        'assigned_at',
        'notes',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
