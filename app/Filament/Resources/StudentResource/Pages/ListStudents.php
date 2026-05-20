<?php

namespace App\Filament\Resources\StudentResource\Pages;

use App\Filament\Resources\StudentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords
{
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('+ Tambah Siswa Baru'),
        ];
    }

    // ===== TABS =====
    // Tab di atas tabel untuk filter cepat per status
    // WHY tabs? Lebih cepat dari filter — satu klik langsung filter
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Semua')
                ->badge(\App\Models\Student::count()),

            'new' => Tab::make('Baru')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('global_status', 'new')
                )
                ->badge(
                    \App\Models\Student::where('global_status', 'new')->count()
                )
                ->badgeColor('warning'),

            'active' => Tab::make('Aktif')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('global_status', 'active')
                )
                ->badge(
                    \App\Models\Student::where('global_status', 'active')->count()
                )
                ->badgeColor('info'),

            'registered' => Tab::make('Terdaftar')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('global_status', 'registered')
                )
                ->badge(
                    \App\Models\Student::where('global_status', 'registered')->count()
                )
                ->badgeColor('success'),

            'cancelled' => Tab::make('Dibatalkan')
                ->modifyQueryUsing(
                    fn(Builder $query) => $query->where('global_status', 'cancelled')
                )
                ->badge(
                    \App\Models\Student::where('global_status', 'cancelled')->count()
                )
                ->badgeColor('danger'),
        ];
    }
}
