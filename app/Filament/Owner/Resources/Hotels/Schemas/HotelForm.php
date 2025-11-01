<?php

namespace App\Filament\Owner\Resources\Hotels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class HotelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('city_id')
                    ->required()
                    ->numeric(),
                TextInput::make('owner_id')
                    ->required()
                    ->numeric(),
                TextInput::make('name')
                    ->required(),
                TextInput::make('slug')
                    ->required(),
                TextInput::make('star_rating')
                    ->required()
                    ->numeric()
                    ->default(3),
                TextInput::make('address')
                    ->required(),
                TextInput::make('phone')
                    ->tel()
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('average_rating')
                    ->required()
                    ->numeric()
                    ->default(0.0),
                TextInput::make('total_reviews')
                    ->required()
                    ->numeric()
                    ->default(0),
                TextInput::make('status')
                    ->required()
                    ->default('active'),
                Textarea::make('images')
                    ->default(null)
                    ->columnSpanFull(),
            ]);
    }
}
