<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use App\Filament\Resources\FollowUpResource;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\StudentVisit;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\DatePicker;

class ViewStudent extends ViewRecord
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Tombol Follow Up langsung dari halaman detail siswa
            Actions\Action::make('do_follow_up')
                ->label('+ Follow Up')
                ->icon('heroicon-o-phone-arrow-up-right')
                ->color('primary')
                ->modalHeading(fn() => 'Follow Up: ' . $this->getRecord()->name)
                ->modalWidth('lg')
                ->form(FollowUpResource::getFollowUpFormSchema())
                ->action(function (array $data): void {
                    $record = $this->getRecord();

                    $record->followUps()->create([
                        'user_id'        => Auth::id(),
                        'status'         => $data['status'],
                        'notes'          => $data['notes'],
                        'follow_up_date' => $data['follow_up_date'],
                        'method'         => $data['method'],
                    ]);

                    $record->update([
                        'follow_up_status'  => $data['status'],
                        'last_follow_up_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Follow up berhasil dicatat')
                        ->success()
                        ->send();

                    // Refresh halaman agar histori langsung terupdate
                    $this->refreshFormData([
                        'follow_up_status',
                        'last_follow_up_at',
                    ]);
                }),

            Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([

            // ===== SECTION 1: Data Siswa =====
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

            // ===== SECTION 2: Status =====
            Infolists\Components\Section::make('Status')
                ->icon('heroicon-o-tag')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('global_status')
                        ->label('Status Global')
                        ->badge()
                        ->formatStateUsing(
                            fn($state) =>
                            Student::GLOBAL_STATUS[$state] ?? $state
                        )
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
                        ->formatStateUsing(
                            fn($state) =>
                            Student::FOLLOW_UP_STATUS[$state] ?? $state
                        )
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
                        ->formatStateUsing(
                            fn($state) =>
                            Student::PAYMENT_STATUS[$state] ?? $state
                        )
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

            // ===== SECTION: Riwayat Kunjungan =====
            Infolists\Components\Section::make('Riwayat Kunjungan')
                ->icon('heroicon-o-calendar-days')
                ->description('Histori setiap kedatangan siswa ke stand PSB')
                ->collapsible()
                ->headerActions([
                    // Tombol Tambah Kunjungan
                    Infolists\Components\Actions\Action::make('tambah_kunjungan')
                        ->label('+ Tambah Kunjungan')
                        ->icon('heroicon-o-plus-circle')
                        ->color('success')
                        ->form([
                            DatePicker::make('visit_date')
                                ->label('Tanggal Kunjungan')
                                ->required()
                                ->default(today())
                                ->maxDate(today()),

                            Select::make('user_id')
                                ->label('Dilayani Oleh')
                                ->options(
                                    \App\Models\User::where('is_active', true)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->default(Auth::id())
                                ->searchable()
                                ->required(),

                            Textarea::make('notes')
                                ->label('Catatan Kunjungan')
                                ->placeholder('contoh: Tanya soal biaya, datang bersama orang tua...')
                                ->rows(2),
                        ])
                        ->action(function (array $data, $record): void {
                            // Hitung nomor kunjungan berikutnya
                            $nextVisitNumber = $record->studentVisits()->max('visit_number') + 1;

                            $record->studentVisits()->create([
                                'user_id'      => $data['user_id'],
                                'visit_number' => $nextVisitNumber,
                                'visit_date'   => $data['visit_date'],
                                'notes'        => $data['notes'] ?? null,
                            ]);

                            // Update visit_date di students ke kunjungan terbaru
                            $record->update([
                                'visit_date'   => $data['visit_date'],
                                'pic_user_id'  => $data['user_id'],
                            ]);

                            Notification::make()
                                ->title('Kunjungan ke-' . $nextVisitNumber . ' berhasil dicatat')
                                ->success()
                                ->send();
                        }),
                ])
                ->schema([
                    Infolists\Components\RepeatableEntry::make('studentVisits')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('visit_number')
                                ->label('Kunjungan')
                                ->formatStateUsing(fn($state) => 'Ke-' . $state)
                                ->badge()
                                ->color('gray'),

                            Infolists\Components\TextEntry::make('visit_date')
                                ->label('Tanggal')
                                ->date('d M Y'),

                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Dilayani Oleh')
                                ->badge()
                                ->color('info'),

                            Infolists\Components\TextEntry::make('notes')
                                ->label('Catatan')
                                ->placeholder('—')
                                ->columnSpan(1),
                        ])
                        ->columns(4)
                        ->contained(false),

                    // Summary di bawah tabel kunjungan
                    Infolists\Components\TextEntry::make('studentVisits')
                        ->label('Ringkasan')
                        ->formatStateUsing(function ($record) {
                            $total = $record->studentVisits()->count();
                            $latest = $record->studentVisits()
                                ->latest('visit_date')
                                ->first();

                            if (!$latest) return 'Belum ada kunjungan tercatat';

                            return "Total {$total}x kunjungan | " .
                                "Terakhir: " . $latest->visit_date->format('d M Y') .
                                " oleh " . ($latest->user?->name ?? '-');
                        })
                        ->columnSpanFull(),
                ]),

            // ===== SECTION 3: Histori Follow Up =====
            Infolists\Components\Section::make('Histori Follow Up')
                ->icon('heroicon-o-clock')
                ->description('Semua catatan komunikasi dengan siswa ini')
                ->schema([
                    Infolists\Components\RepeatableEntry::make('followUps')
                        ->label('')
                        ->schema([
                            Infolists\Components\TextEntry::make('follow_up_date')
                                ->label('Tanggal')
                                ->date('d M Y'),

                            Infolists\Components\TextEntry::make('status')
                                ->label('Hasil')
                                ->badge()
                                ->formatStateUsing(
                                    fn($state) =>
                                    FollowUp::STATUS[$state] ?? $state
                                )
                                ->color(fn(string $state): string => match ($state) {
                                    'hot'            => 'danger',
                                    'warm'           => 'warning',
                                    'cold'           => 'info',
                                    'closing'        => 'success',
                                    'not_interested' => 'gray',
                                    default          => 'gray',
                                }),

                            Infolists\Components\TextEntry::make('method')
                                ->label('Metode')
                                ->formatStateUsing(
                                    fn($state) =>
                                    FollowUp::METHOD[$state] ?? $state
                                ),

                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Dicatat oleh'),

                            Infolists\Components\TextEntry::make('notes')
                                ->label('Catatan')
                                ->columnSpanFull()
                                ->placeholder('—'),
                        ])
                        ->columns(4)
                        ->contained(false), // tampilan lebih lega
                ]),

            // ===== SECTION 4: Catatan =====
            Infolists\Components\Section::make('Catatan Umum')
                ->icon('heroicon-o-chat-bubble-left-ellipsis')
                ->collapsed(true) // collapsed by default agar tidak memenuhi layar
                ->schema([
                    Infolists\Components\TextEntry::make('notes')
                        ->label('')
                        ->placeholder('Tidak ada catatan')
                        ->columnSpanFull(),
                ]),
        ]);
    }
}
