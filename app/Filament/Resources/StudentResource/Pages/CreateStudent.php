<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Models\Student;
use App\Models\AcademicYear;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Illuminate\Support\Facades\Auth;

class CreateStudent extends CreateRecord
{
    protected static string $resource = StudentResource::class;

    // Judul halaman
    public function getTitle(): string | Htmlable
    {
        return 'Tambah Siswa Baru';
    }

    // Redirect ke mana setelah berhasil create
    // WHY ke ViewStudent? Supaya panitia langsung lihat
    // data yang baru diinput untuk verifikasi
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function afterCreate(): void
    {
        // Auto-assign user yang login sebagai PIC pertama
        $this->record->pics()->syncWithoutDetaching([
            Auth::id() => [
                'assigned_at' => now(),
                'notes'       => 'PIC pertama (otomatis saat input)',
            ],
        ]);

        // hitung kunjungan siswa
        $record = $this->getRecord();

        $record->studentVisits()->create([
            'user_id'      => Auth::id(),
            'visit_number' => 1,
            'visit_date'   => $record->visit_date,
            'notes'        => 'Kunjungan pertama',
        ]);
    }

    // Notifikasi sukses yang custom
    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->title('✅ Data siswa berhasil ditambahkan')
            ->body('Siswa ' . $this->getRecord()->name . ' berhasil didaftarkan.')
            ->success();
    }

    // Hook: dijalankan sebelum data disimpan
    // WHY? Untuk menambahkan data otomatis yang tidak ada di form
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Definisikan $academicYearId PERTAMA sebelum dipakai dimanapun
        $academicYearId = \App\Models\AcademicYear::where('is_active', true)
            ->value('id') ?? 1;

        // Cek duplikat nomor HP saat klik Create
        $existing = Student::where('no_hp', $data['no_hp'])
            ->where('academic_year_id', $data['academic_year_id'])
            ->first();

        if ($existing) {
            Notification::make()
                ->title('⚠️ Nomor HP sudah terdaftar!')
                ->body(
                    "Siswa {$existing->name} sudah terdaftar sejak " .
                        $existing->visit_date->format('d M Y') .
                        ". PIC: " . ($existing->picUser?->name ?? '-')
                )
                ->warning()
                ->persistent()
                ->actions([
                    NotificationAction::make('lihat')
                        ->label('Lihat Data Siswa')
                        ->url(
                            StudentResource::getUrl('view', ['record' => $existing])
                        )
                        ->openUrlInNewTab(),
                ])
                ->send();

            $this->halt();
        }

        // Pastikan school_id terisi
        if (empty($data['school_id'])) {
            $data['school_id'] = Auth::user()?->school_id ?? 1;
        }

        // Auto isi academic_year_id
        // Isi otomatis dari sistem, tidak perlu input manual
        $data['academic_year_id'] = $academicYearId;
        $data['school_id'] = \Illuminate\Support\Facades\Auth::user()?->school_id ?? 1;

        return $data;
    }
}
