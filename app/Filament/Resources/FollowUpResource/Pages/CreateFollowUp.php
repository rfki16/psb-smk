<?php

namespace App\Filament\Resources\FollowUpResource\Pages;

use App\Filament\Resources\FollowUpResource;
use Filament\Resources\Pages\CreateRecord;

class CreateFollowUp extends CreateRecord
{
    protected static string $resource = FollowUpResource::class;

    // Redirect langsung ke list jika ada yang akses halaman ini langsung
    public function mount(): void
    {
        $this->redirect(FollowUpResource::getUrl('index'));
    }
}
