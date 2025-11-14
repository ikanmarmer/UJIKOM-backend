<?php

namespace App\Filament\Owner\Resources\Reviews;

use App\Enums\ReviewStatus;
use App\Filament\Owner\Resources\Reviews\Pages\CreateReview;
use App\Filament\Owner\Resources\Reviews\Pages\EditReview;
use App\Filament\Owner\Resources\Reviews\Pages\ListReviews;
use App\Filament\Owner\Resources\Reviews\Pages\ViewReview;
use App\Filament\Owner\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Owner\Resources\Reviews\Schemas\ReviewInfolist;
use App\Filament\Owner\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;
    protected static ?string $navigationLabel = 'Review';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Konten';
    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('status', ReviewStatus::APPROVED)
            ->whereHas('hotel', function ($query) {
                $query->where('owner_id', auth()->id());
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->count();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'view' => ViewReview::route('/{record}'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}
