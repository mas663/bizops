<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\ImageColumn;
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
                        ->defaultImageUrl(asset('images/product-placeholder.svg')),
                    TextColumn::make('name')
                        ->label('Nama')
                        ->weight(FontWeight::Bold)
                        ->searchable(),
                    TextColumn::make('category.name')
                        ->label('Kategori')
                        ->badge()
                        ->placeholder('Tanpa kategori'),
                    TextColumn::make('starting_price')
                        ->label('Harga')
                        ->formatStateUsing(fn (?int $state): string => $state === null
                            ? 'Belum ada varian aktif'
                            : 'mulai Rp '.number_format($state, 0, ',', '.')),
                ]),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Status Aktif'),
            ])
            ->defaultSort('sort_order')
            ->recordActions([
                EditAction::make(),
                Action::make('toggleActive')
                    ->label(fn (Product $record): string => $record->is_active ? 'Nonaktifkan' : 'Aktifkan')
                    ->icon(fn (Product $record): Heroicon => $record->is_active ? Heroicon::OutlinedXCircle : Heroicon::OutlinedCheckCircle)
                    ->color(fn (Product $record): string => $record->is_active ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->action(fn (Product $record) => $record->update(['is_active' => ! $record->is_active])),
            ])
            ->toolbarActions([]);
    }
}
