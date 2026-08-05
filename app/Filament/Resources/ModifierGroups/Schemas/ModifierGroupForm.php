<?php

namespace App\Filament\Resources\ModifierGroups\Schemas;

use App\Enums\SelectionType;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ModifierGroupForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama')
                    ->required()
                    ->maxLength(255)
                    ->validationMessages([
                        'required' => 'Nama wajib diisi.',
                        'max' => 'Nama maksimal 255 karakter.',
                    ]),
                Radio::make('selection_type')
                    ->label('Tipe Pilihan')
                    ->options([
                        SelectionType::Single->value => 'Tunggal',
                        SelectionType::Multiple->value => 'Ganda',
                    ])
                    ->default(SelectionType::Single->value)
                    ->required()
                    ->inline()
                    ->validationMessages([
                        'required' => 'Tipe pilihan wajib dipilih.',
                    ]),
                Toggle::make('is_required')
                    ->label('Wajib Dipilih')
                    ->default(false),
                Toggle::make('is_active')
                    ->label('Aktif')
                    ->default(true),
                Repeater::make('options')
                    ->relationship()
                    ->label('Opsi')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Opsi')
                            ->required()
                            ->maxLength(255)
                            ->validationMessages([
                                'required' => 'Nama opsi wajib diisi.',
                                'max' => 'Nama opsi maksimal 255 karakter.',
                            ]),
                        TextInput::make('sort_order')
                            ->label('Urutan')
                            ->integer()
                            ->default(0)
                            ->required()
                            ->validationMessages([
                                'required' => 'Urutan wajib diisi.',
                                'integer' => 'Urutan harus berupa angka bulat.',
                            ]),
                        Toggle::make('is_active')
                            ->label('Aktif')
                            ->default(true),
                    ])
                    ->columns(3)
                    ->orderColumn('sort_order')
                    ->deletable(false)
                    ->reorderableWithButtons()
                    ->addActionLabel('Tambah Opsi')
                    ->itemLabel(fn (array $state): ?string => $state['name'] ?? null)
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]);
    }
}
