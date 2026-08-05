<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Closure;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\RawJs;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Dasar')
                    ->collapsible()
                    ->schema([
                        FileUpload::make('image')
                            ->label('Foto Produk')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->visibility('public')
                            ->directory('products')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state) {
                                if ($get('receipt_name_is_manual')) {
                                    return;
                                }

                                $set('receipt_name', mb_substr($state ?? '', 0, 20));
                            }),
                        Select::make('category_id')
                            ->label('Kategori')
                            ->relationship('category', 'name')
                            ->searchable()
                            ->preload(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ]),
                Section::make('Pengaturan Lanjutan')
                    ->collapsible()
                    ->collapsed()
                    ->schema([
                        Hidden::make('receipt_name_is_manual')
                            ->dehydrated(false)
                            ->afterStateHydrated(function (Set $set, ?Product $record) {
                                $set('receipt_name_is_manual', filled($record?->receipt_name));
                            }),
                        TextInput::make('receipt_name')
                            ->label('Nama di Nota')
                            ->required()
                            ->maxLength(20)
                            ->placeholder('Nama singkat untuk nota thermal')
                            ->helperText('Maksimal 20 karakter — nota thermal 58mm hanya memuat sekitar 32 karakter per baris.')
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (Set $set) => $set('receipt_name_is_manual', true)),
                        Select::make('modifierGroups')
                            ->label('Grup Modifier')
                            ->relationship('modifierGroups', 'name')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->helperText('Opsional — kosongkan jika produk ini tidak punya personalisasi apa pun.')
                            ->columnSpanFull(),
                    ]),
                Repeater::make('variants')
                    ->relationship()
                    ->label('Varian')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Varian')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('price')
                            ->label('Harga Jual')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make("\$money(\$input, '.', 0)"))
                            ->stripCharacters('.')
                            ->integer()
                            ->minValue(0),
                        TextInput::make('cost_price')
                            ->label('HPP')
                            ->required()
                            ->prefix('Rp')
                            ->mask(RawJs::make("\$money(\$input, '.', 0)"))
                            ->stripCharacters('.')
                            ->integer()
                            ->minValue(0),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->integer()
                            ->default(0)
                            ->required(),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(5)
                    ->orderColumn('sort_order')
                    ->deletable(false)
                    ->reorderableWithButtons()
                    ->addActionLabel('Tambah Varian')
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->defaultItems(1)
                    ->minItems(1)
                    ->validationMessages([
                        'min' => 'Produk harus punya minimal satu varian.',
                    ])
                    ->rule(static function (): Closure {
                        return static function (string $attribute, $value, Closure $fail) {
                            $hasActiveVariant = collect($value ?? [])
                                ->contains(fn (array $variant): bool => (bool) ($variant['is_active'] ?? false));

                            if (! $hasActiveVariant) {
                                $fail('Produk harus punya minimal satu varian yang aktif.');
                            }
                        };
                    })
                    ->columnSpanFull(),
            ]);
    }
}
