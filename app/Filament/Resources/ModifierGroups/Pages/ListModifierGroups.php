<?php

namespace App\Filament\Resources\ModifierGroups\Pages;

use App\Filament\Resources\ModifierGroups\ModifierGroupResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListModifierGroups extends ListRecords
{
    protected static string $resource = ModifierGroupResource::class;

    protected static ?string $breadcrumb = 'Daftar';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Grup Modifier'),
        ];
    }
}
