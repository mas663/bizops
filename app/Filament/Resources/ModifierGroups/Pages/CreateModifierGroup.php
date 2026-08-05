<?php

namespace App\Filament\Resources\ModifierGroups\Pages;

use App\Filament\Resources\ModifierGroups\ModifierGroupResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateModifierGroup extends CreateRecord
{
    protected static string $resource = ModifierGroupResource::class;

    protected static ?string $breadcrumb = 'Tambah';

    public function getTitle(): string | Htmlable
    {
        return 'Tambah Grup Modifier';
    }

    protected function getCreateFormAction(): Action
    {
        return parent::getCreateFormAction()->label('Simpan');
    }

    protected function getCreateAnotherFormAction(): Action
    {
        return parent::getCreateAnotherFormAction()->label('Simpan & Tambah Lagi');
    }

    protected function getCancelFormAction(): Action
    {
        return parent::getCancelFormAction()->label('Batal');
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Grup modifier berhasil ditambahkan';
    }
}
