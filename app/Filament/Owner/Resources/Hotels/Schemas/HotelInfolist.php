<?php

namespace App\Filament\Owner\Resources\Hotels\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class HotelInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('city_id')
                    ->numeric(),
                TextEntry::make('owner_id')
                    ->numeric(),
                TextEntry::make('name'),
                TextEntry::make('slug'),
                TextEntry::make('star_rating')
                    ->numeric(),
                TextEntry::make('address'),
                TextEntry::make('phone'),
                TextEntry::make('email')
                    ->label('Email address'),
                TextEntry::make('average_rating')
                    ->numeric(),
                TextEntry::make('total_reviews')
                    ->numeric(),
                TextEntry::make('status'),
                TextEntry::make('images')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
