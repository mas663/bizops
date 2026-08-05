<?php

namespace App\Filament\Resources\ModifierGroups\Tables;

use App\Enums\SelectionType;
use App\Filament\Resources\ModifierGroups\ModifierGroupResource;
use App\Models\ModifierGroup;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ModifierGroupsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari')
            ->recordUrl(fn (ModifierGroup $record): string => ModifierGroupResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label('Wajib/Opsional')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedMinusCircle)
                    ->tooltip(fn (bool $state): string => $state ? 'Wajib' : 'Opsional'),
                TextColumn::make('selection_type')
                    ->label('Tipe Pilihan')
                    ->formatStateUsing(fn (SelectionType $state): string => match ($state) {
                        SelectionType::Single => 'Tunggal',
                        SelectionType::Multiple => 'Ganda',
                    })
                    ->badge(),
                TextColumn::make('options_count')
                    ->label('Jumlah Opsi')
                    ->counts('options'),
                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean()
                    ->tooltip(fn (bool $state): string => $state ? 'Aktif' : 'Nonaktif'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif')
                    ->placeholder('Semua')
                    ->default(true),
            ])
            ->defaultSort('name', 'asc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
