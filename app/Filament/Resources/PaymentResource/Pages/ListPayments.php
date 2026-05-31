<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Student;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    protected function getHeaderActions(): array
    {
        return []; // Tidak ada tombol Create di sini
    }

    public function getTabs(): array
    {
        return [
            'semua' => Tab::make('Semua')
                ->query(fn(Builder $query) => $query),

            'unpaid' => Tab::make('Belum Bayar')
                ->query(fn(Builder $query) => $query->where('payment_status', 'unpaid'))
                ->badge(
                    Student::where('payment_status', 'unpaid')
                        ->whereNotIn('global_status', ['cancelled'])
                        ->count()
                )
                ->badgeColor('danger'),

            'dp' => Tab::make('DP')
                ->query(fn(Builder $query) => $query->where('payment_status', 'dp'))
                ->badge(
                    Student::where('payment_status', 'dp')
                        ->whereNotIn('global_status', ['cancelled'])
                        ->count()
                )
                ->badgeColor('warning'),

            'paid' => Tab::make('Lunas')
                ->query(fn(Builder $query) => $query->where('payment_status', 'paid'))
                ->badgeColor('success'),
        ];
    }
}
