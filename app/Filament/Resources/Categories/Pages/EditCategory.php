<?php

namespace App\Filament\Resources\Categories\Pages;

use App\Filament\Resources\Categories\CategoryResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

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
        return 'Kategori berhasil disimpan';
    }
}
