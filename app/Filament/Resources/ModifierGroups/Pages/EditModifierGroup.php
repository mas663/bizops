<?php

namespace App\Filament\Resources\ModifierGroups\Pages;

use App\Filament\Resources\ModifierGroups\ModifierGroupResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditModifierGroup extends EditRecord
{
    protected static string $resource = ModifierGroupResource::class;

    protected static ?string $breadcrumb = 'Ubah';

    public function getTitle(): string | Htmlable
    {
        return "Ubah {$this->getRecordTitle()}";
    }

    protected function getSaveFormAction(): Action
    {
        return parent::getSaveFormAction()->label('Simpan Perubahan');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Grup modifier berhasil disimpan';
    }
}
