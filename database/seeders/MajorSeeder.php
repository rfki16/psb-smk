<?php

namespace Database\Seeders;

use App\Models\Major;
use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::where('slug', 'smk-kesehatan-gc')->first();

        if (!$school) {
            $this->command->error('Sekolah tidak ditemukan, jalankan seeder');
            return;
        }

        $majors = [
            [
                'school_id'   => $school->id,
                'name'        => 'Keperawatan',
                'code'        => 'KEP',
                'description' => 'Asisten Keperawatan dan Caregiver',
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'school_id'   => $school->id,
                'name'        => 'Farmasi',
                'code'        => 'FM',
                'description' => 'Farmasi Klinis dan Komunitas',
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'school_id'   => $school->id,
                'name'        => 'Teknik Laboratorium Medik',
                'code'        => 'TLM',
                'description' => 'Analis Kesehatan',
                'is_active'   => true,
                'sort_order'  => 3,
            ]
        ];

        foreach ($majors as $major) {
            Major::firstOrCreate(
                [
                    'school_id' => $major['school_id'],
                    'name'      => $major['name']
                ],
                $major
            );
        }

        $this->command->info('Major seeder selesai');
        $this->command->table(
            ['Kode', 'Jurusan'],
            array_map(fn($m) => [$m['code'], $m['name']], $majors)
        );
    }
}
