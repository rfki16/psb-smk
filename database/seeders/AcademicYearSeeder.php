<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ambil sekolah
        $school = School::where('slug', 'smk-kesehatan-gc')->first();

        if (!$school) {
            $this->command->error('Sekolah tidak ditemukan, jalankan seeder');
        }

        $years = [
            [
                'school_id'     => $school->id,
                'name'          => '2025/2026',
                'start_date'    => '2024-09-01',
                'end_date'      => '2025-07-31',
                'is_active'     => false,
            ],
            [
                'school_id'     => $school->id,
                'name'          => '2026/2027',
                'start_date'    => '2025-09-01',
                'end_date'      => '2026-07-31',
                'is_active'     => true,

            ],
        ];

        foreach ($years as $year) {
            AcademicYear::firstOrCreate(
                [
                    'school_id' => $year['school_id'],
                    'name'      => $year['name']
                ],
                $year
            );
        }
        $this->command->info('Academic Year seeder selesai');
    }
}
