<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Student;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PaymentResource extends Resource
{
    // WHY model Student, bukan Payment?
    // Karena kita tampilkan DAFTAR SISWA + status bayarnya,
    // sama persis dengan pola Follow Up.
    protected static ?string $model = Student::class;

    protected static ?string $navigationIcon       = 'heroicon-o-banknotes';
    protected static ?string $navigationLabel      = 'Pembayaran';
    protected static ?string $navigationGroup      = 'PSB';
    protected static ?string $slug                 = 'pembayaran';
    protected static ?int    $navigationSort        = 3;

    public static function getNavigationBadge(): ?string
    {
        // Tampilkan jumlah siswa yang belum bayar sama sekali
        $count = Student::where('payment_status', 'unpaid')
            ->whereNotIn('global_status', ['cancelled', 'done'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        // Form ini tidak dipakai langsung (aksi via modal di tabel)
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                // Tampilkan semua siswa kecuali cancelled
                Student::query()
                    ->whereNotIn('global_status', ['cancelled'])
                    ->with(['payments', 'pics'])
                    ->latest()
            )
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Siswa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('no_hp')
                    ->label('No. HP')
                    ->searchable(),

                Tables\Columns\TextColumn::make('major.name')
                    ->label('Jurusan')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Status Bayar')
                    ->badge()
                    ->color(fn(string $state) => match ($state) {
                        'unpaid' => 'danger',
                        'dp'     => 'warning',
                        'paid'   => 'success',
                        default  => 'gray',
                    })
                    ->formatStateUsing(fn(string $state) => match ($state) {
                        'unpaid' => 'Belum Bayar',
                        'dp'     => 'DP',
                        'paid'   => 'Lunas',
                        default  => '-',
                    }),

                Tables\Columns\TextColumn::make('total_paid')
                    ->label('Total Dibayar')
                    ->formatStateUsing(
                        fn($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.')
                    )
                    ->getStateUsing(fn(Student $record) => $record->payments->sum('amount')),

                Tables\Columns\TextColumn::make('first_payment_date')
                    ->label('Tgl Bayar')
                    ->date('d M Y')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('pics.name')
                    ->label('PIC')
                    ->badge()
                    ->separator(',')
                    ->color('info'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('payment_status')
                    ->label('Status Bayar')
                    ->options([
                        'unpaid' => 'Belum Bayar',
                        'dp'     => 'DP',
                        'paid'   => 'Lunas',
                    ]),

                Tables\Filters\SelectFilter::make('major_id')
                    ->label('Jurusan')
                    ->relationship('major', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('tambah_pembayaran')
                    ->label('Tambah Bayar')
                    ->icon('heroicon-o-plus-circle')
                    ->color('success')
                    // Siswa cancelled atau sudah lunas tidak bisa tambah bayar
                    ->hidden(
                        fn(Student $record) =>
                        $record->global_status === 'cancelled' ||
                            $record->payment_status === 'paid'
                    )
                    ->form([
                        Forms\Components\Grid::make(2)->schema([
                            Forms\Components\TextInput::make('amount')
                                ->label('Jumlah Bayar (Rp)')
                                ->numeric()
                                ->minValue(1)
                                ->required()
                                ->prefix('Rp'),

                            Forms\Components\Select::make('type')
                                ->label('Jenis Pembayaran')
                                ->options([
                                    'dp'    => 'DP (Uang Muka)',
                                    'lunas' => 'Lunas',
                                ])
                                ->required(),

                            Forms\Components\Select::make('method')
                                ->label('Metode Bayar')
                                ->options([
                                    'tunai'    => 'Tunai',
                                    'transfer' => 'Transfer Bank',
                                ])
                                ->required(),

                            Forms\Components\DatePicker::make('payment_date')
                                ->label('Tanggal Bayar')
                                ->default(now())
                                ->required(),

                            Forms\Components\TextInput::make('reference_number')
                                ->label('No. Referensi / Kode Transfer')
                                ->placeholder('Opsional')
                                ->columnSpanFull(),

                            Forms\Components\Textarea::make('notes')
                                ->label('Catatan')
                                ->rows(2)
                                ->columnSpanFull(),
                        ]),
                    ])
                    ->action(function (array $data, Student $record) {
                        // 1. Simpan ke tabel payments
                        $record->payments()->create([
                            'user_id'          => Auth::id(),
                            'amount'           => $data['amount'],
                            'type'             => $data['type'],
                            'method'           => $data['method'],
                            'reference_number' => $data['reference_number'] ?? null,
                            'payment_date'     => $data['payment_date'],
                            'notes'            => $data['notes'] ?? null,
                        ]);

                        // 2. Update payment_status di students
                        $updateData = [
                            'payment_status' => $data['type'], // dp atau lunas
                        ];

                        if (!$record->first_payment_date) {
                            $updateData['first_payment_date'] = $data['payment_date'];
                        }

                        // 3. Business rule: jika lunas → paid_at + global_status naik ke registered
                        if ($data['type'] === 'lunas') {
                            $updateData['paid_at'] = now();

                            // Hanya naikkan ke registered jika status masih di bawahnya
                            // (jangan turunkan jika sudah tested/done)
                            if (in_array($record->global_status, ['new', 'active'])) {
                                $updateData['global_status'] = 'registered';
                            }
                        }

                        $record->update($updateData);

                        Notification::make()
                            ->title('Pembayaran berhasil dicatat')
                            ->body("Total dibayar: Rp " . number_format(
                                $record->fresh()->payments->sum('amount'),
                                0,
                                ',',
                                '.'
                            ))
                            ->success()
                            ->send();
                    }),

                Tables\Actions\Action::make('lihat_histori')
                    ->label('Histori')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading(fn(Student $record) => 'Histori Pembayaran — ' . $record->name)
                    ->modalContent(function (Student $record) {
                        $payments = $record->payments()->with('user')->get();
                        $total    = 'Rp ' . number_format($payments->sum('amount'), 0, ',', '.');

                        $rows = $payments->map(
                            fn($p) =>
                            "<tr class='border-b dark:border-gray-700'>
                <td class='py-2 px-3'>{$p->payment_date->format('d M Y')}</td>
                <td class='py-2 px-3'>{$p->type_label}</td>
                <td class='py-2 px-3'>{$p->method_label}</td>
                <td class='py-2 px-3 font-semibold'>{$p->formatted_amount}</td>
                <td class='py-2 px-3 text-gray-500'>" . ($p->reference_number ?? '-') . "</td>
                <td class='py-2 px-3'>" . ($p->user->name ?? '-') . "</td>
            </tr>"
                        )->join('');

                        $html = "
            <div class='overflow-x-auto text-sm'>
                <table class='w-full text-left'>
                    <thead class='text-xs uppercase bg-gray-50 dark:bg-gray-800 text-gray-500'>
                        <tr>
                            <th class='py-2 px-3'>Tanggal</th>
                            <th class='py-2 px-3'>Jenis</th>
                            <th class='py-2 px-3'>Metode</th>
                            <th class='py-2 px-3'>Jumlah</th>
                            <th class='py-2 px-3'>No. Referensi</th>
                            <th class='py-2 px-3'>Dicatat Oleh</th>
                        </tr>
                    </thead>
                    <tbody>{$rows}</tbody>
                    <tfoot class='bg-gray-50 dark:bg-gray-800 font-bold'>
                        <tr>
                            <td colspan='3' class='py-2 px-3 text-right'>Total:</td>
                            <td colspan='3' class='py-2 px-3'>{$total}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>";

                        return new \Illuminate\Support\HtmlString($html);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Tutup')
                    ->visible(fn(Student $record) => $record->payments()->exists()),
            ])
            ->headerActions([
                // Rekap summary di atas tabel
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
