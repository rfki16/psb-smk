<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $schools = [
            [
                'name'      => 'SMK Kesehatan Global Cendekia',
                'slug'      => 'smk-kesehatan-gc',
                'address'   => 'Jl. Gadang No. 83-85, Jakarta Utara',
                'phone'     => '021-1234567',
                'email'     => 'globalcendekiapusat@gmail.com',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            School::firstOrCreate(

                ['slug' => $school['slug']],
                $school

            );
        }

        $this->command->info('School seeder selesai.');
    }
}
