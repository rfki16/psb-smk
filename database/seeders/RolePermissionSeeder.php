<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Wajib: reset cache permission Spatie sebelum membuat role/permission baru
        // WHY: Spatie menyimpan permission di cache. Kalau tidak direset,
        // perubahan tidak akan langsung terdeteksi

        // reset cache permission
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $roles = [
            'super_admin'     => 'Akses penuh ke semua fitur & semua sekolah',
            'admin'           => 'Akses penuh untuk satu sekolah + pengaturan',
            'front_office'    => 'Buku tamu & follow up',
            'berkas'          => 'Pengumpulan & verifikasi berkas',
            'tim_kesehatan'   => 'Pemeriksaan fisik',
            'petugas_seragam' => 'Pengukuran seragam',
            'dokter'          => 'Wawancara & kelulusan',
        ];

        foreach ($roles as $roleName => $description) {
            Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web']
            );
        }

        $this->command->info('✅ Role seeder selesai:');
        $this->command->table(
            ['Role', 'Deskripsi'],
            array_map(
                fn($role, $desc) => [$role, $desc],
                array_keys($roles),
                array_values($roles)
            )
        );
    }
}
