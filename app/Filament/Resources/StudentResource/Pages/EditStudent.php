<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditStudent extends EditRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol View di halaman Edit
            Actions\ViewAction::make(),
            // Tombol Delete (soft delete)
            Actions\DeleteAction::make(),
        ];
    }

    // Setelah edit, kembali ke halaman View
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->title('✅ Data siswa berhasil diperbarui')
            ->success();
    }

    // Validasi business rule sebelum menyimpan perubahan
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->getRecord();

        // Business Rule: Jika payment_status = paid,
        // global_status minimal harus registered
        if (
            isset($data['payment_status']) &&
            $data['payment_status'] === 'paid' &&
            in_array($data['global_status'] ?? $record->global_status, ['new', 'active'])
        ) {
            $data['global_status'] = 'registered';

            // Isi timestamp paid_at jika belum diisi
            if (empty($record->paid_at)) {
                $data['paid_at'] = now();
            }
        }

        return $data;
    }

    // Setelah data siswa tersimpan, sync student_pics
    protected function afterSave(): void
    {
        $record = $this->getRecord();

        // Kalau pic_user_id berubah, tambahkan ke student_pics
        if ($record->pic_user_id) {
            $record->pics()->syncWithoutDetaching([
                $record->pic_user_id => [
                    'assigned_at' => now(),
                    'notes'       => 'Diperbarui saat edit data siswa',
                ],
            ]);
        }
    }
}
