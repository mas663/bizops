<?php

namespace App\Filament\Resources\Categories\Tables;

use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->searchPlaceholder('Cari')
            ->recordUrl(fn (Category $record): string => CategoryResource::getUrl('edit', ['record' => $record]))
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('sort_order')
                    ->label('Urutan')
                    ->sortable(),
                TextColumn::make('products_count')
                    ->label('Jumlah Produk')
                    ->counts('products'),
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
            ->reorderable('sort_order')
            ->defaultSort('name', 'asc')
            ->recordActions([])
            ->toolbarActions([]);
    }
}
