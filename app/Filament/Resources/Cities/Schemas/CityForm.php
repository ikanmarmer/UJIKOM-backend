<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class CityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Informasi Kota')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Kota')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(
                                fn(string $state, Set $set) =>
                                $set('slug', Str::slug($state))
                            ),

                        TextInput::make('province')
                            ->label('Provinsi')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Versi URL-friendly dari nama kota'),

                        FileUpload::make('image')
                            ->label('Gambar')
                            ->image()
                            ->directory('cities')
                            ->disk('public')
                            ->imageEditor()
                            ->maxSize(2048)
                            ->helperText('Gambar thumbnail kota (opsional)')
                            ->columnSpanFull(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
