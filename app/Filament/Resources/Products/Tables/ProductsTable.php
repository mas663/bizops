<?php

namespace App\Filament\Resources\Products\Tables;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Support\Enums\TextSize;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
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
            ->recordUrl(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record]))
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
                        ->imageHeight('auto')
                        ->extraImgAttributes([
                            'class' => 'w-full aspect-[3/4] object-cover',
                        ])
                        ->defaultImageUrl(asset('images/product-placeholder.svg')),
                    TextColumn::make('name')
                        ->label('Nama')
                        ->weight('bold')
                        ->size(TextSize::Small)
                        ->searchable(),
                    Split::make([
                        TextColumn::make('category.name')
                            ->label('Kategori')
                            ->placeholder('Tanpa kategori'),
                        TextColumn::make('starting_price')
                            ->label('Harga')
                            ->weight('bold')
                            ->size(TextSize::Medium)
                            ->alignEnd()
                            ->formatStateUsing(fn (?int $state): string => $state === null
                                ? 'Belum ada varian aktif'
                                : 'Rp '.number_format($state, 0, ',', '.')),
                    ]),
                ]),
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
