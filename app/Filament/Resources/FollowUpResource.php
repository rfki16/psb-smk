<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FollowUpResource\Pages;
use App\Models\FollowUp;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class FollowUpResource extends Resource
{
    protected static ?string $model = Student::class;

    // WHY model = Student, bukan FollowUp?
    // Karena halaman utama menampilkan DAFTAR SISWA yang perlu di-follow up,
    // bukan daftar catatan follow up. Kita beroperasi pada konteks siswa,
    // lalu menyimpan catatan follow up sebagai aksi.

    protected static ?string $navigationIcon    = 'heroicon-o-phone-arrow-up-right';
    protected static ?string $navigationLabel   = 'Follow Up';
    protected static ?string $modelLabel        = 'Follow Up';
    protected static ?string $pluralModelLabel  = 'Data Follow Up';
    protected static ?string $navigationGroup   = 'PSB';
    protected static ?int    $navigationSort    = 2;

    // ============================================
    // FORM — Form untuk input catatan follow up
    // Form ini dipakai di dalam Modal Action (bukan halaman terpisah)
    // ============================================
    public static function form(Form $form): Form
    {
        return $form->schema([
            // Section ini tidak dipakai langsung
            // Form sebenarnya ada di getFollowUpFormSchema() di bawah
        ]);
    }

    // Form schema yang dipakai di dalam modal
    // WHY dipisah? Supaya bisa dipakai ulang di Action maupun halaman
    public static function getFollowUpFormSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->label('Hasil Follow Up')
                ->options(FollowUp::STATUS)
                ->required()
                ->native(false), // tampilan dropdown yang lebih cantik

            Forms\Components\Select::make('method')
                ->label('Metode Kontak')
                ->options(FollowUp::METHOD)
                ->default('whatsapp')
                ->required()
                ->native(false),

            Forms\Components\DatePicker::make('follow_up_date')
                ->label('Tanggal Follow Up')
                ->default(today())
                ->required()
                ->maxDate(today()),

            Forms\Components\Textarea::make('notes')
                ->label('Catatan')
                ->placeholder('Tulis hasil percakapan, respon siswa, janji, dll...')
                ->required()
                ->rows(4)
                ->maxLength(1000),
        ];
    }

    // ============================================
    // TABLE — Daftar siswa yang perlu di-follow up
    // ============================================
    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->description(
                        fn(Student $record) =>
                        $record->school_origin . ' • ' . $record->no_hp
                    ),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No HP')
                    ->searchable()
                    ->copyable()
                    ->copyMessage('Nomor disalin!')
                    ->icon('heroicon-m-phone')
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('follow_up_status')
                    ->label('Status FU')
                    ->badge()
                    ->placeholder('Belum di-FU')
                    ->formatStateUsing(
                        fn($state) =>
                        Student::FOLLOW_UP_STATUS[$state] ?? $state
                    )
                    ->color(fn(?string $state): string => match ($state) {
                        'hot'            => 'danger',
                        'warm'           => 'warning',
                        'cold'           => 'info',
                        'closing'        => 'success',
                        'not_interested' => 'gray',
                        default          => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
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

                // Kolom "Terakhir FU" — ini kunci efisiensi panitia
                // Panitia bisa langsung lihat siapa yang sudah lama tidak di-FU
                Tables\Columns\TextColumn::make('last_follow_up_at')
                    ->label('Terakhir FU')
                    ->placeholder('Belum pernah')
                    ->since()  // format: "3 hari lalu", "1 minggu lalu"
                    ->sortable()
                    ->color(
                        fn(Student $record): string =>
                        $record->followUpPriorityColor()
                    ),

                Tables\Columns\TextColumn::make('followUps_count')
                    ->label('Total FU')
                    ->counts('followUps') // hitung relasi followUps
                    ->badge()
                    ->color('info'),

                Tables\Columns\TextColumn::make('picUser.name')
                    ->label('PIC')
                    ->placeholder('—')
                    ->toggleable(),

            ])

            // ===== FILTER =====
            ->filters([

                Tables\Filters\SelectFilter::make('follow_up_status')
                    ->label('Status Follow Up')
                    ->options(Student::FOLLOW_UP_STATUS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(Student::PAYMENT_STATUS)
                    ->multiple(),

                // Filter khusus: siswa yang belum di-FU sama sekali
                Tables\Filters\Filter::make('never_followed_up')
                    ->label('Belum pernah di-Follow Up')
                    ->query(
                        fn(Builder $q) =>
                        $q->whereNull('last_follow_up_at')
                    )
                    ->toggle(), // tampil sebagai toggle switch

                // Filter khusus: siswa yang sudah lama tidak di-FU (lebih dari 7 hari)
                Tables\Filters\Filter::make('overdue_follow_up')
                    ->label('Belum di-FU lebih dari 7 hari')
                    ->query(
                        fn(Builder $q) =>
                        $q->where(function ($query) {
                            $query->whereNull('last_follow_up_at')
                                ->orWhere('last_follow_up_at', '<=', now()->subDays(7));
                        })
                    )
                    ->toggle(),

                Tables\Filters\SelectFilter::make('pic_user_id')
                    ->label('PIC Panitia')
                    ->relationship('picUser', 'name'),

            ])

            // ===== ACTIONS — Tombol aksi per baris =====
            ->actions([

                // AKSI UTAMA: Buka modal follow up langsung dari baris tabel
                // WHY modal? Panitia tidak perlu pindah halaman, lebih cepat
                Tables\Actions\Action::make('do_follow_up')
                    ->label('Follow Up')
                    ->icon('heroicon-o-phone-arrow-up-right')
                    ->color('primary')
                    ->modalHeading(
                        fn(Student $record) =>
                        'Follow Up: ' . $record->name
                    )
                    ->modalDescription(
                        fn(Student $record) =>
                        'HP: ' . $record->no_hp .
                            ' | Status saat ini: ' .
                            (Student::FOLLOW_UP_STATUS[$record->follow_up_status] ?? 'Belum di-FU')
                    )
                    ->modalWidth('lg')
                    // Form di dalam modal
                    ->form(self::getFollowUpFormSchema())
                    // Aksi saat tombol Submit di modal diklik
                    ->action(function (Student $record, array $data): void {
                        // 1. Simpan catatan follow up ke tabel follow_ups
                        $record->followUps()->create([
                            'user_id'        => Auth::id(),
                            'status'         => $data['status'],
                            'notes'          => $data['notes'],
                            'follow_up_date' => $data['follow_up_date'],
                            'method'         => $data['method'],
                        ]);

                        // 2. Update status follow up dan timestamp di tabel students
                        $record->update([
                            'follow_up_status'  => $data['status'],
                            'last_follow_up_at' => now(),
                            // Jika statusnya closing, update global_status ke active
                            'global_status' => $data['status'] === 'closing'
                                ? 'registered'
                                : $record->global_status,
                        ]);

                        // 3. Tampilkan notifikasi sukses
                        Notification::make()
                            ->title('Follow up berhasil dicatat')
                            ->body(
                                $record->name . ' → ' .
                                    (FollowUp::STATUS[$data['status']] ?? $data['status'])
                            )
                            ->success()
                            ->send();
                    })
                    // Sembunyikan tombol ini untuk siswa yang sudah cancelled
                    ->hidden(fn(Student $record) => $record->global_status === 'cancelled'),

                // Tombol lihat histori lengkap
                Tables\Actions\Action::make('view_history')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->url(
                        fn(Student $record) =>
                        StudentResource::getUrl('view', ['record' => $record])
                    ),

            ])

            // ===== BULK ACTIONS =====
            ->bulkActions([
                // Bulk: ubah PIC untuk banyak siswa sekaligus
                Tables\Actions\BulkAction::make('assign_pic')
                    ->label('Ganti PIC')
                    ->icon('heroicon-o-user')
                    ->form([
                        Forms\Components\Select::make('pic_user_id')
                            ->label('Pilih PIC Baru')
                            ->options(
                                User::role(['front_office', 'admin'])
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->required(),
                    ])
                    ->action(function ($records, array $data): void {
                        $records->each->update(['pic_user_id' => $data['pic_user_id']]);
                        Notification::make()
                            ->title('PIC berhasil diperbarui')
                            ->success()
                            ->send();
                    }),
            ])

            // Default: urutkan siswa yang paling lama tidak di-FU di atas
            // WHY? Supaya panitia langsung tahu siapa yang paling urgent
            ->defaultSort('last_follow_up_at', 'asc')
            // Tampilkan 25 data per halaman
            ->paginationPageOptions([10, 25, 50, 100])  // opsi pilihan per halaman
            ->defaultPaginationPageOption(25)
            ->striped();
    }

    // ============================================
    // QUERY — Hanya tampilkan siswa yang perlu di-follow up
    // ============================================
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['major', 'picUser', 'followUps'])
            ->withCount('followUps')
            // Exclude siswa yang sudah selesai (done) atau dibatalkan
            ->whereNotIn('global_status', ['done', 'cancelled'])
            // Exclude siswa yang sudah lunas (tidak perlu di-FU lagi)
            ->where('payment_status', '!=', 'paid');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFollowUps::route('/'),
            // Tidak ada halaman create/edit terpisah
            // Semua aksi dilakukan via modal di halaman list
        ];
    }

    // Badge: jumlah siswa yang belum pernah di-follow up
    public static function getNavigationBadge(): ?string
    {
        $count = Student::whereNull('last_follow_up_at')
            ->whereNotIn('global_status', ['done', 'cancelled'])
            ->where('payment_status', '!=', 'paid')
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }
}
