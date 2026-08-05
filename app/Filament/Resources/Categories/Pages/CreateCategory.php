<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Contracts\Support\Htmlable;

class CreateCategory extends CreateRecord
{
    protected static string $resource = CategoryResource::class;

    protected static ?string $breadcrumb = 'Tambah';

    public function getTitle(): string | Htmlable
    {
        return 'Tambah Kategori';
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
        return 'Kategori berhasil ditambahkan';
    }
}
