<?php

namespace App\Filament\Resources\ModifierGroups\Tables;

use App\Enums\SelectionType;
use App\Models\ModifierGroup;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_required')
                    ->label('Wajib/Opsional')
                    ->boolean()
                    ->trueIcon(Heroicon::OutlinedCheckCircle)
                    ->falseIcon(Heroicon::OutlinedMinusCircle),
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
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (ModifierGroup $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (ModifierGroup $record): Heroicon => $record->is_active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (ModifierGroup $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (ModifierGroup $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->toolbarActions([]);
    }
}
