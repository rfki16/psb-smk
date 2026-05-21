<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;
use App\Models\Student;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }

    // ============================================
    // INFOLIST — Tampilan detail data (bukan form)
    // WHY Infolist? Lebih rapi untuk tampilan read-only
    // dibanding form yang disabled
    // ============================================
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            Infolists\Components\Section::make('Data Siswa')
                ->icon('heroicon-o-user')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('name')
                        ->label('Nama Lengkap')
                        ->weight('bold')
                        ->size('lg')
                        ->columnSpan(2),

                    Infolists\Components\TextEntry::make('visit_date')
                        ->label('Tanggal Datang')
                        ->date('d F Y'),

                    Infolists\Components\TextEntry::make('school_origin')
                        ->label('Asal Sekolah'),

                    Infolists\Components\TextEntry::make('parent_name')
                        ->label('Nama Orang Tua'),

                    Infolists\Components\TextEntry::make('no_hp')
                        ->label('Nomor HP')
                        ->copyable()
                        ->icon('heroicon-m-phone'),

                    Infolists\Components\TextEntry::make('major.name')
                        ->label('Minat Jurusan')
                        ->badge()
                        ->color('info')
                        ->placeholder('Belum dipilih'),

                    Infolists\Components\TextEntry::make('picUser.name')
                        ->label('PIC Panitia')
                        ->placeholder('—'),

                    Infolists\Components\TextEntry::make('academicYear.name')
                        ->label('Tahun Ajaran'),
                ]),

            Infolists\Components\Section::make('Status')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([

                    Infolists\Components\TextEntry::make('global_status')
                        ->label('Status Global')
                        ->badge()
                        ->formatStateUsing(fn($state) => Student::GLOBAL_STATUS[$state] ?? $state)
                        ->color(fn(string $state): string => match ($state) {
                            'new'        => 'gray',
                            'active'     => 'info',
                            'registered' => 'warning',
                            'tested'     => 'success',
                            'done'       => 'success',
                            'cancelled'  => 'danger',
                            default      => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('follow_up_status')
                        ->label('Status Follow Up')
                        ->badge()
                        ->placeholder('Belum di-follow up')
                        ->formatStateUsing(fn($state) => Student::FOLLOW_UP_STATUS[$state] ?? $state)
                        ->color(fn(?string $state): string => match ($state) {
                            'hot'     => 'danger',
                            'warm'    => 'warning',
                            'cold'    => 'info',
                            'closing' => 'success',
                            default   => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('payment_status')
                        ->label('Status Pembayaran')
                        ->badge()
                        ->formatStateUsing(fn($state) => Student::PAYMENT_STATUS[$state] ?? $state)
                        ->color(fn(string $state): string => match ($state) {
                            'unpaid' => 'danger',
                            'dp'     => 'warning',
                            'paid'   => 'success',
                            default  => 'gray',
                        }),

                    Infolists\Components\TextEntry::make('last_follow_up_at')
                        ->label('Terakhir Follow Up')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Belum pernah'),

                    Infolists\Components\TextEntry::make('paid_at')
                        ->label('Tanggal Lunas')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Belum bayar'),

                    Infolists\Components\TextEntry::make('tested_at')
                        ->label('Tanggal Tes')
                        ->dateTime('d M Y, H:i')
                        ->placeholder('Belum tes'),
                ]),

            Infolists\Components\Section::make('Catatan')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->schema([
                    Infolists\Components\TextEntry::make('notes')
                        ->label('')
                        ->placeholder('Tidak ada catatan')
                        ->columnSpanFull(),
                ]),

        ]);
    }
}
