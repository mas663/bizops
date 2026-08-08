<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use App\Support\MoneyInput;
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
                            ->placeholder('Seret & lepas file di sini, atau <span class="filepond--label-action">Jelajahi</span>')
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Nama Produk')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Nama produk wajib diisi.',
                                'max' => 'Nama produk maksimal 255 karakter.',
                            ])
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
                            ->validationMessages([
                                'required' => 'Nama di nota wajib diisi.',
                                'max' => 'Nama di nota maksimal 20 karakter.',
                            ])
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
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Nama varian wajib diisi.',
                                'max' => 'Nama varian maksimal 255 karakter.',
                            ]),
                        TextInput::make('price')
                            ->label('Harga Jual')
                            ->required()
                            ->prefix('Rp')
                            ->mask(MoneyInput::mask())
                            ->stripCharacters(MoneyInput::THOUSANDS_SEPARATOR)
                            ->integer()
                            ->minValue(0)
                            ->validationMessages([
                                'required' => 'Harga jual wajib diisi.',
                                'integer' => 'Harga jual harus berupa angka bulat.',
                                'min' => 'Harga jual tidak boleh negatif.',
                            ]),
                        TextInput::make('cost_price')
                            ->label('HPP')
                            ->required()
                            ->prefix('Rp')
                            ->mask(MoneyInput::mask())
                            ->stripCharacters(MoneyInput::THOUSANDS_SEPARATOR)
                            ->integer()
                            ->minValue(0)
                            ->validationMessages([
                                'required' => 'HPP wajib diisi.',
                                'integer' => 'HPP harus berupa angka bulat.',
                                'min' => 'HPP tidak boleh negatif.',
                            ]),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(4)
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
