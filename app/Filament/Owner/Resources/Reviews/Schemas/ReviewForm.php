<?php

namespace App\Filament\Owner\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('booking_id')
                    ->required()
                    ->numeric(),
                TextInput::make('user_id')
                    ->required()
                    ->numeric(),
                TextInput::make('hotel_id')
                    ->tel()
                    ->required()
                    ->numeric(),
                TextInput::make('rating')
                    ->required()
                    ->numeric(),
                Textarea::make('comment')
                    ->default(null)
                    ->columnSpanFull(),
                Textarea::make('images')
                    ->default(null)
                    ->columnSpanFull(),
                Select::make('status')
                    ->options(ReviewStatus::class)
                    ->default('pending')
                    ->required(),
            ]);
    }
}
