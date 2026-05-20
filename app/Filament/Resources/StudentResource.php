<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentResource\Pages;
use App\Models\AcademicYear;
use App\Models\Major;
use App\Models\Student;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Override;

class StudentResource extends Resource
{
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    // Label di sidebar & heading halaman
    protected static ?string $navigationLabel = 'Buku Tamu';
    protected static ?string $modelLabel = 'Siswa';
    protected static ?string $pluralModelLabel = 'Data Siswa';

    // Grup navigasi di sidebar
    protected static ?string $navigationGroup = 'PSB';

    // Urutan di dalam grup
    protected static ?int $navigationSort = 1;

    // Kolom yang dicari saat pakai Global Search Filament (Ctrl+K)
    protected static ?string $recordTitleAttribute = 'name';


    // ============================================
    // FORM — Definisi form input siswa
    // ============================================
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Data Siswa')
                    ->description('Informasi dasar calon siswa')
                    ->icon('heroicon-o-user')
                    ->columns(2)
                    ->schema([

                        Forms\Components\TextInput::make('name')
                            ->label('Nama Lengkap Siswa')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(2), // ambil 2 kolom (full width dalam section)

                        Forms\Components\TextInput::make('school_origin')
                            ->label('Asal Sekolah')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('parent_name')
                            ->label('Nama Orang Tua / Wali')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('no_hp')
                            ->label('Nomor HP')
                            ->required()
                            ->maxLength(20)
                            ->tel()
                            ->unique(
                                table: 'students',
                                column: 'no_hp',
                                ignorable: fn($record) => $record, // ignore record sendiri saat edit
                            )
                            ->helperText('Nomor HP harus unik, digunakan sebagai identifikasi siswa'),

                        Forms\Components\DatePicker::make('visit_date')
                            ->label('Tanggal Kedatangan')
                            ->required()
                            ->default(now()) // default hari ini
                            ->maxDate(now()), // tidak boleh tanggal masa depan
                    ]),

                Forms\Components\Section::make('Informasi PSB')
                    ->description('Data terkait proses penerimaan')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('academic_year_id')
                            ->label('Tahun Ajaran')
                            ->required()
                            ->options(
                                // tampilkan semua tahun ajaran yang aktif
                                AcademicYear::where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->default(
                                AcademicYear::where('is_active', true)->value('id')
                            )
                            ->searchable(),

                        Forms\Components\TextInput::make('major_id')
                            ->label('Minat Jurusan')
                            ->placeholder('-- Pilih Jurusan --')
                            ->options(
                                Major::active()->ordered()->pluck('name', 'id')
                            )
                            ->searchable()
                            ->nullable(),

                        Forms\Components\Select::make('user_pic_id')
                            ->label('Panitia yang Melayani (PIC)')
                            ->placeholder('-- Pilih Panitia --')
                            ->options(
                                // Hanya tampilkan user yang punya role front_office atau admin
                                User::role(['front_office', 'admin'])
                                    ->where('is_active', true)
                                    ->pluck('name', 'id')
                            )
                            ->default(fn() => Auth::id()) // default: user yang sedang login
                            ->searchable()
                            ->required(),

                        Forms\Components\TextInput::make('school_id')
                            ->default(
                                // Untuk sekarang hardcode school_id = 1
                                // Nanti akan diganti dengan school_id dari user yang login
                                fn() => fn() => Auth::user()?->school_id ?? 1
                            ),
                    ]),

                Forms\Components\Section::make('Status Awal')
                    ->description('Status siswa saat pertama kali datang')
                    ->icon('heroicon-o-tag')
                    ->columns(3)
                    ->schema([
                        Forms\Components\Select::make('global_status')
                            ->label('Status Global')
                            ->options(Student::GLOBAL_STATUS)
                            ->default('new')
                            ->required(),

                        Forms\Components\Select::make('follow_up_status')
                            ->label('Status Follow Up')
                            ->options(Student::FOLLOW_UP_STATUS)
                            ->placeholder('-- Belum di-follow up --')
                            ->nullable(),

                        Forms\Components\Select::make('payment_status')
                            ->label('Status Pembayaran')
                            ->options(Student::PAYMENT_STATUS)
                            ->default('unpaid')
                            ->required(),
                    ]),

                Forms\Components\Section::make('Catatan')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan Panitia')
                            ->placeholder('Tulis catatan khusus jika ada...')
                            ->rows(3)
                            ->maxLength(1000)
                            ->nullable(),
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([

                // Nomor urut otomatis
                Tables\Columns\TextColumn::make('index')
                    ->label('No.')
                    ->rowIndex()
                    ->width(50),

                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()    // bisa dicari
                    ->sortable()      // bisa diurutkan
                    ->weight('bold'), // teks tebal

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->copyable()      // klik untuk copy ke clipboard
                    ->copyMessage('Nomor HP disalin!')
                    ->icon('heroicon-m-phone'),

                Tables\Columns\TextColumn::make('school_origin')
                    ->label('Asal Sekolah')
                    ->searchable()
                    ->toggleable(), // bisa disembunyikan/ditampilkan oleh user

                Tables\Columns\TextColumn::make('major.name')
                    ->label('Minat Jurusan')
                    ->placeholder('—') // tampilkan — kalau kosong
                    ->badge()          // tampilkan sebagai badge
                    ->color('info'),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable()
                    ->copyable()      // klik untuk copy ke clipboard
                    ->copyMessage('Nomor HP disalin!')
                    ->icon('heroicon-m-phone'),

                // Badge status global dengan warna berbeda per nilai
                Tables\Columns\TextColumn::make('global_status')
                    ->label('Status')
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

                Tables\Columns\TextColumn::make('follow_up_status')
                    ->label('Follow Up')
                    ->badge()
                    ->placeholder('—')
                    ->formatStateUsing(fn($state) => Student::FOLLOW_UP_STATUS[$state] ?? $state)
                    ->color(fn(?string $state): string => match ($state) {
                        'hot'            => 'danger',   // merah = panas
                        'warm'           => 'warning',  // kuning = hangat
                        'cold'           => 'info',     // biru = dingin
                        'closing'        => 'success',  // hijau = deal
                        'not_interested' => 'gray',
                        default          => 'gray',
                    }),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Pembayaran')
                    ->badge()
                    ->formatStateUsing(fn($state) => Student::PAYMENT_STATUS[$state] ?? $state)
                    ->color(fn(string $state): string => match ($state) {
                        'unpaid' => 'danger',
                        'dp'     => 'warning',
                        'paid'   => 'success',
                        default  => 'gray',
                    }),

                Tables\Columns\TextColumn::make('picUser.name')
                    ->label('PIC')
                    ->placeholder('—')
                    ->toggleable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('visit_date')
                    ->label('Tgl Datang')
                    ->date('d M Y') // format: 25 Jan 2025
                    ->sortable()
                    ->toggleable(),
            ])

            // ===== FILTER =====
            // Filter yang muncul di panel kanan tabel
            ->filters([

                Tables\Filters\SelectFilter::make('global_status')
                    ->label('Status Global')
                    ->options(Student::GLOBAL_STATUS)
                    ->multiple(), // bisa pilih lebih dari satu

                Tables\Filters\SelectFilter::make('follow_up_status')
                    ->label('Status Follow Up')
                    ->options(Student::FOLLOW_UP_STATUS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Pembayaran')
                    ->options(Student::PAYMENT_STATUS)
                    ->multiple(),

                Tables\Filters\SelectFilter::make('major_id')
                    ->label('Jurusan')
                    ->relationship('major', 'name'),

                Tables\Filters\SelectFilter::make('pic_user_id')
                    ->label('PIC Panitia')
                    ->relationship('picUser', 'name'),

                Tables\Filters\Filter::make('visit_date')
                    ->label('Tanggal Kedatangan')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('Dari Tanggal'),
                        Forms\Components\DatePicker::make('until')
                            ->label('Sampai Tanggal'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn($q) => $q->whereDate('visit_date', '>=', $data['from'])
                            )
                            ->when(
                                $data['until'],
                                fn($q) => $q->whereDate('visit_date', '<=', $data['until'])
                            );
                    }),

                // Filter: tampilkan data yang sudah dihapus (soft delete)
                Tables\Filters\TrashedFilter::make(),

            ])

            // ===== ACTIONS — Tombol aksi per baris =====
            ->actions([

                // Tombol View (lihat detail)
                Tables\Actions\ViewAction::make()
                    ->label(''),

                // Tombol Edit
                Tables\Actions\EditAction::make()
                    ->label(''),

                // Tombol ubah status — dropdown kecil
                Tables\Actions\ActionGroup::make([

                    Tables\Actions\Action::make('mark_active')
                        ->label('Tandai Aktif')
                        ->icon('heroicon-o-check-circle')
                        ->color('info')
                        ->action(function (Student $record): void {
                            if ($record->isCancelled()) {
                                Notification::make()
                                    ->title('Tidak bisa mengubah status')
                                    ->body('Siswa yang dibatalkan tidak dapat diaktifkan kembali.')
                                    ->danger()
                                    ->send();
                                return;
                            }
                            $record->update(['global_status' => 'active']);
                            Notification::make()
                                ->title('Status diubah ke Aktif')
                                ->success()
                                ->send();
                        })
                        ->visible(fn(Student $record) => $record->global_status === 'new'),

                    Tables\Actions\Action::make('mark_cancelled')
                        ->label('Batalkan Pendaftaran')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->requiresConfirmation() // muncul dialog konfirmasi
                        ->modalHeading('Batalkan Pendaftaran Siswa?')
                        ->modalDescription('Data siswa tidak akan dihapus, hanya statusnya yang berubah menjadi Dibatalkan.')
                        ->modalSubmitActionLabel('Ya, Batalkan')
                        ->action(function (Student $record): void {
                            $record->update(['global_status' => 'cancelled']);
                            Notification::make()
                                ->title('Pendaftaran dibatalkan')
                                ->warning()
                                ->send();
                        })
                        ->visible(fn(Student $record) => $record->global_status !== 'cancelled'),

                ])->label('Aksi')->icon('heroicon-m-ellipsis-vertical'),

                // Tombol Restore (untuk data yang sudah di-soft delete)
                Tables\Actions\RestoreAction::make(),

            ])

            // ===== BULK ACTIONS — Aksi untuk banyak baris sekaligus =====
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus yang dipilih'),
                    Tables\Actions\RestoreBulkAction::make()
                        ->label('Restore yang dipilih'),
                ]),
            ])
            // Default sort: data terbaru di atas
            ->defaultSort('created_at', 'desc')

            // Tampilkan 25 data per halaman
            ->paginate(25)

            // Stripe rows — baris bergantian warna, lebih mudah dibaca
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudents::route('/'),
            'create' => Pages\CreateStudent::route('/create'),
            'view'  => Pages\ViewStudent::route('/{record'),
            'edit' => Pages\EditStudent::route('/{record}/edit')
        ];
    }

    // ============================================
    // ELOQUENT QUERY — Query dasar untuk tabel
    // Selalu eager load relasi yang dipakai di tabel
    // untuk menghindari N+1 query problem
    // ============================================
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['major', 'picUser', 'academicYear'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class, // tampilkan data soft delete juga
            ]);
    }

    // ============================================
    // NAVIGATION BADGE
    // Tampilkan jumlah siswa baru di icon sidebar
    // ============================================
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('global_status', 'new')->count() ?: null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
