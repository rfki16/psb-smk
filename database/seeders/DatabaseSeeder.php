<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // URUTAN INI PENTING — jangan diubah urutannya!
            // Setiap seeder bergantung pada data dari seeder sebelumnya
            SchoolSeeder::class,         // 1. Buat data sekolah dulu 
            AcademicYearSeeder::class,   // 2. Buat tahun ajaran (butuh school)
            MajorSeeder::class,          // 3. Buat jurusan (butuh school)
            RolePermissionSeeder::class, // 4. Buat roles (tidak bergantung)
            UserSeeder::class,           // 5. Buat users (butuh school & role)
            SettingSeeder::class,        // 6. Buat settings (butuh school)
        ]);

        $this->command->info('');
        $this->command->info('Semua seeder selesai!');
        $this->command->info('');
        $this->command->info('');
        $this->command->info('');
        $this->command->info('');
    }
}
