<?php

namespace App\Filament\Resources\Products\Pages;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected static ?string $breadcrumb = 'Daftar';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Tambah Produk'),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make('Semua'),
        ];

        foreach (Category::query()->where('is_active', true)->orderBy('name')->get() as $category) {
            $tabs[$category->id] = Tab::make($category->name)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('category_id', $category->id));
        }

        return $tabs;
    }
}
