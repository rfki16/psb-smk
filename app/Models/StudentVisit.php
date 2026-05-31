<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentVisit extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'user_id',
        'visit_number',
        'visit_date',
        'notes',
    ];

    protected $casts = [
        'visit_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
