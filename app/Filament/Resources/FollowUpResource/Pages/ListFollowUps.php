<?php

namespace App\Filament\Resources\FollowUpResource\Pages;

use App\Filament\Resources\FollowUpResource;
use App\Models\Student;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListFollowUps extends ListRecords
{
    protected static string $resource = FollowUpResource::class;

    // Tidak ada tombol "Tambah" di header
    // WHY? Follow up dilakukan via modal, bukan halaman create terpisah
    protected function getHeaderActions(): array
    {
        return [];
    }

    // ===== TABS — Filter cepat per prioritas =====
    public function getTabs(): array
    {
        // Query dasar: siswa aktif yang belum bayar penuh
        $base = Student::whereNotIn('global_status', ['done', 'cancelled'])
            ->where('payment_status', '!=', 'paid');

        return [

            // Tab 1: Semua yang perlu di-FU
            'all' => Tab::make('Semua')
                ->badge($base->count()),

            // Tab 2: Belum pernah di-FU sama sekali — PRIORITAS TERTINGGI
            'never' => Tab::make('Belum Pernah di-FU')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->whereNull('last_follow_up_at')
                )
                ->badge(
                    (clone $base)->whereNull('last_follow_up_at')->count()
                )
                ->badgeColor('danger'),

            // Tab 3: Sudah lama tidak di-FU (lebih dari 7 hari)
            'overdue' => Tab::make('Overdue (7+ hari)')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->where('last_follow_up_at', '<=', now()->subDays(7))
                )
                ->badge(
                    (clone $base)->where('last_follow_up_at', '<=', now()->subDays(7))->count()
                )
                ->badgeColor('warning'),

            // Tab 4: Siswa Hot — kemungkinan closing tinggi
            'hot' => Tab::make('Hot 🔥')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->where('follow_up_status', 'hot')
                )
                ->badge(
                    (clone $base)->where('follow_up_status', 'hot')->count()
                )
                ->badgeColor('danger'),

            // Tab 5: Siswa Warm
            'warm' => Tab::make('Warm ☀️')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->where('follow_up_status', 'warm')
                )
                ->badge(
                    (clone $base)->where('follow_up_status', 'warm')->count()
                )
                ->badgeColor('warning'),

            // Tab 6: No Response — perlu strategi berbeda
            'no_response' => Tab::make('No Response')
                ->modifyQueryUsing(
                    fn(Builder $q) =>
                    $q->where('follow_up_status', 'no_response')
                )
                ->badge(
                    (clone $base)->where('follow_up_status', 'no_response')->count()
                )
                ->badgeColor('gray'),

        ];
    }
}
