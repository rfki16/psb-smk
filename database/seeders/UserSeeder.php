<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $school = School::where('slug', 'smk-kesehatan-gc')->first();

        $users = [
            // Super Admin — tidak terikat sekolah manapun
            [
                'email'     => 'superadmin@psbgc.com',
                'name'      => 'Super Administrator',
                'phone'     => '08100000001',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => null, // super admin tidak terikat sekolah
                'role'      => 'super_admin',
            ],
            // Admin sekolah
            [
                'email'     => 'admin@psbgc.com',
                'name'      => 'Admin PSB',
                'phone'     => '085172228362',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'admin',
            ],
            // Panitia Front Office
            [
                'email'     => 'fo1@psbgc.com',
                'name'      => 'Rifki',
                'phone'     => '08100000003',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'front_office',
            ],
            // Panitia Berkas
            [
                'email'     => 'berkas@psbgc.com',
                'name'      => 'Dapodik',
                'phone'     => '08100000004',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'berkas',
            ],
            // Tim Kesehatan
            [
                'email'     => 'kesehatan@psbgc.com',
                'name'      => 'Tim Kesehatan',
                'phone'     => '08100000005',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'tim_kesehatan',
            ],
            // Petugas Seragam
            [
                'email'     => 'seragam@psbgc.com',
                'name'      => 'Tim Seragam',
                'phone'     => '08100000006',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'petugas_seragam',
            ],
            // Dokter
            [
                'email'     => 'dokter@psbgc.com',
                'name'      => 'dr. Anggraini Zaenab, MM',
                'phone'     => '08100000007',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'school_id' => $school?->id,
                'role'      => 'dokter',
            ],
        ];

        foreach ($users as $userData) {
            $role = $userData['role'];
            unset($userData['role']); // kolom role tidak ada di table user jadi harus pisah

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                $userData
            );

            $user->syncRoles([$role]);
        }

        $this->command->info('✅ User seeder selesai:');
        $this->command->table(
            ['Email', 'Nama', 'Role', 'Password'],
            array_map(fn($u) => [
                $u['email'],
                $u['name'],
                // role sudah di-unset, ambil dari array asli
                'lihat di atas',
                'password',
            ], $users)
        );

        // Tampilkan tabel kredensial yang lebih informatif
        $this->command->info('');
        $this->command->info('📋 Kredensial login:');
        $this->command->table(
            ['Email', 'Role', 'Password'],
            [
                ['superadmin@psbgc.com', 'super_admin',     'password'],
                ['admin@psbgc.com',      'admin',            'password'],
                ['fo1@psbgc.com',        'front_office',     'password'],
                ['berkas@psbgc.com',     'berkas',           'password'],
                ['kesehatan@psbgc.com',  'tim_kesehatan',    'password'],
                ['seragam@psbgc.com',    'petugas_seragam',  'password'],
                ['dokter@psbgc.com',     'dokter',           'password'],
            ]
        );
    }
}
