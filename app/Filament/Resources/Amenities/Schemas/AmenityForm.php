<?php

namespace App\Filament\Resources\Amenities\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AmenityForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nama Fasilitas')
                    ->placeholder('Masukkan nama fasilitas...')
                    ->required()
                    ->extraAttributes([
                        'class' => 'rounded-xl border-gray-300 focus:border-primary-500 focus:ring-2 focus:ring-primary-400 transition-all duration-200 ease-in-out',
                    ])
                    ->prefixIcon('heroicon-o-sparkles')
                    ->autofocus(),
            ]);
    }
}
