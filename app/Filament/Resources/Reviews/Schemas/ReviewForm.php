<?php

namespace App\Filament\Resources\Reviews\Schemas;

use App\Enums\ReviewStatus;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ReviewForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Status Ulasan')
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options([
                                ReviewStatus::PENDING->value => ReviewStatus::PENDING->label(),
                                ReviewStatus::APPROVED->value => ReviewStatus::APPROVED->label(),
                                ReviewStatus::REJECTED->value => ReviewStatus::REJECTED->label(),
                            ])
                            ->required()
                            ->native(false)
                            ->helperText('Ubah status ulasan (akan memperbarui rating hotel jika disetujui)')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
