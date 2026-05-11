<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Major extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'school_id',
        'name',
        'code',
        'description',
        'is_active',
        'sort_order',

    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',

    ];

    // ===== RELASI =====

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // Siswa yang berminat di jurusan ini (minat awal)
    public function students()
    {
        return $this->hasMany(Student::class, 'major_id');
    }

    // Siswa yang akhirnya masuk jurusan ini (hasil dokter)
    public function finalStudents()
    {
        return $this->hasMany(DoctorReview::class, 'final_major_id');
    }

    // ===== SCOPE =====
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForSchool($query, int $schoolId)
    {
        return $query->where('school_id', $schoolId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
