<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Setting;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $school = School::where('slug', 'smk-kesehatan-gc')->first();

        if (!$school) {
            $this->command->error('Sekolah tidak ditemukan.');
            return;
        }

        $settings = [
            [
                'key'         => 'max_students_per_session',
                'value'       => '10',
                'type'        => 'integer',
                'description' => 'Maksimal siswa per sesi tes kesehatan',
            ],
            [
                'key'         => 'session_times',
                'value' => json_encode([
                    ['name' => 'Pagi - Sesi 1',  'start' => '08:00', 'end' => '09:00'],
                    ['name' => 'Pagi - Sesi 2',  'start' => '10:00', 'end' => '11:00'],
                    ['name' => 'Siang - Sesi 1', 'start' => '13:00', 'end' => '14:00'],
                    ['name' => 'Siang - Sesi 2', 'start' => '15:00', 'end' => '16:00'],
                ]),
                'type'        => 'json',
                'description' => 'Jam pelaksanaan sesi tes (pagi & siang)',

            ],
            [
                'key'         => 'wa_driver',
                'value'       => 'dummy',
                'type'        => 'string',
                'description' => 'Driver WhatsApp: dummy | fonnte | meta',
            ],
            [
                'key'         => 'wa_token',
                'value'       => '',
                'type'        => 'string',
                'description' => 'Token/API Key WhatsApp (Fonnte atau Meta)',
            ],
            [
                'key'         => 'wa_group_id',
                'value'       => '',
                'type'        => 'string',
                'description' => 'ID Grup WhatsApp untuk notifikasi panitia',
            ],
            [
                'key'         => 'wa_sender',
                'value'       => '',
                'type'        => 'string',
                'description' => 'Nomor HP pengirim WhatsApp',
            ],
            // Konfigurasi PSB
            [
                'key'         => 'psb_registration_fee',
                'value'       => '500000',
                'type'        => 'integer',
                'description' => 'Biaya pendaftaran PSB (rupiah)',
            ],
            [
                'key'         => 'psb_dp_amount',
                'value'       => '200000',
                'type'        => 'integer',
                'description' => 'Minimal DP untuk bisa ikut tes kesehatan',
            ],

            // Konfigurasi notifikasi
            [
                'key'         => 'notif_invitation_template',
                'value'       => 'Yth. {nama_siswa}, Anda terdaftar untuk mengikuti tes kesehatan PSB SMK pada {tanggal} pukul {jam}. Harap hadir tepat waktu dan membawa berkas lengkap. Info: {no_hp_panitia}',
                'type'        => 'string',
                'description' => 'Template pesan undangan tes kesehatan',
            ],

            // Fitur toggle
            [
                'key'         => 'feature_whatsapp_notification',
                'value'       => 'false',
                'type'        => 'boolean',
                'description' => 'Aktifkan fitur notifikasi WhatsApp',
            ],
            [
                'key'         => 'feature_auto_assign_session',
                'value'       => 'true',
                'type'        => 'boolean',
                'description' => 'Aktifkan auto-assign siswa ke sesi tes',
            ],
        ];

        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                [
                    'school_id' => $school->id,
                    'key'       => $setting['key']
                ],
                array_merge($setting, ['school_id' => $school->id])
            );
        }

        $this->command->info('Setting seeder selesai.');
        $this->command->table(
            ['Key', 'Value', 'Type'],
            array_map(
                fn($s) => [$s['key'], substr($s['value'], 0, 30), $s['type']],
                $settings
            )
        );
    }
}
