<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id',
        'key',
        'value',
        'type',
        'description',

    ];

    // ===== RELASI =====

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    // ===== HELPER STATIC =====
    public static function getValue(
        int $schoolId,
        string $key,
        mixed $default = null
    ): mixed {
        $setting = self::where('school_id', $schoolId)
            ->where('key', $key)
            ->first();

        if (!$setting) return $default;

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'boolean' => filter_var($setting->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /**
     * Set nilai setting (update atau create)
     * 
     * Contoh pakai:
     * Setting::setValue(1, 'max_students_per_session', 15)
     */
    public static function setValue(
        int $schoolId,
        string $key,
        mixed $value
    ): void {
        self::updateOrCreate(
            ['school_id' => $schoolId, 'key' => $key],
            ['value' => is_array($value) ? json_encode($value) : $value]
        );
    }
}
