<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // super admin
        $superAdmin = User::firstOrCreate(
            ['email' => 'superadmin@psb.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '085172228362'
            ]
        );
        $superAdmin->assignRole('super_admin');

        // admin
        $admin = User::firstOrCreate(
            ['email' => 'admin@psb.com'],
            [
                'name' => 'Admin PSB',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '085288273231'
            ]
        );
        $admin->assignRole('admin');

        // front office
        $fo = User::firstOrCreate(
            ['email' => 'frontoffice@psb.com'],
            [
                'name' => 'Panitia Front Office',
                'password' => Hash::make('password'),
                'is_active' => true,
                'phone' => '085256637212'
            ]
        );
        $fo->assignRole('front_office');

        $this->command->info('✅ User seeder berhasil:');
        $this->command->table(
            ['Email', 'Role', 'Password'],
            [
                ['superadmin@psb.com', 'super_admin', 'password'],
                ['admin@psb.com', 'admin', 'password'],
                ['frontoffice@psb.com', 'front_office', 'password'],
            ]
        );
    }
}
