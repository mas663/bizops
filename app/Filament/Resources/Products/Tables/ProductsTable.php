<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\EditAction;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('variants'))
            ->searchPlaceholder('Cari')
            ->contentGrid([
                'default' => 1,
                'sm' => 2,
                'lg' => 3,
            ])
            ->columns([
                Stack::make([
                    ImageColumn::make('image')
                        ->label('Foto')
                        ->disk('public')
                        ->square()
                        ->height('100%')
                        ->extraImgAttributes([
                            'class' => 'w-full aspect-square object-cover',
                        ])
                        ->defaultImageUrl(asset('images/product-placeholder.svg')),
                    TextColumn::make('name')
                        ->label('Nama')
                        ->weight('bold')
                        ->size(TextSize::Small)
                        ->searchable(),
                    TextColumn::make('category.name')
                        ->label('Kategori')
                        ->badge()
                        ->placeholder('Tanpa kategori'),
                    TextColumn::make('starting_price')
                        ->label('Harga')
                        ->weight('bold')
                        ->size(TextSize::Medium)
                        ->formatStateUsing(fn (?int $state): string => $state === null
                            ? 'Belum ada varian aktif'
                            : 'Rp '.number_format($state, 0, ',', '.')),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
                SelectFilter::make('category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name'),
            ])
            ->defaultSort('name', 'asc')
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
