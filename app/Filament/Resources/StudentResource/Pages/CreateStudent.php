<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

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
        // Pastikan school_id terisi
        if (empty($data['school_id'])) {
            $data['school_id'] = auth()->user()?->school_id ?? 1;
        }

        return $data;
    }
}
