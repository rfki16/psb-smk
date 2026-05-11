<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'address',
        'phone',
        'email',
        'logo',
        'is_active',
    ];

    protected $cast = [
        'is_active' => 'boolean',
    ];

    // relasi
    public function academicYears()
    {
        return $this->hasMany(AcademicYear::class);
    }

    public function activeAcademicYear()
    {
        return $this->hasOne(AcademicYear::class)->where('is_active', true);
    }

    // Satu sekolah punya banyak jurusan
    public function majors()
    {
        return $this->hasMany(Major::class);
    }

    // Satu sekolah punya banyak user panitia
    public function users()
    {
        return $this->hasMany(User::class);
    }

    // Satu sekolah punya banyak settings
    public function settings()
    {
        return $this->hasMany(Setting::class);
    }

    // helper ambil nilai
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = $this->settings()->where('key', $key)->first();

        if (!$setting) return $default;

        // Cast nilai sesuai tipe
        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }
}
