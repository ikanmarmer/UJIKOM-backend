<?php

namespace App\Filament\Owner\Resources\RoomCategories;

use App\Filament\Owner\Resources\RoomCategories\Pages\CreateRoomCategory;
use App\Filament\Owner\Resources\RoomCategories\Pages\EditRoomCategory;
use App\Filament\Owner\Resources\RoomCategories\Pages\ListRoomCategories;
use App\Filament\Owner\Resources\RoomCategories\Pages\ViewRoomCategory;
use App\Filament\Owner\Resources\RoomCategories\Schemas\RoomCategoryForm;
use App\Filament\Owner\Resources\RoomCategories\Schemas\RoomCategoryInfolist;
use App\Filament\Owner\Resources\RoomCategories\Tables\RoomCategoriesTable;
use App\Models\RoomCategory;
use BackedEnum;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomCategoryResource extends Resource
{
    protected static ?string $model = RoomCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquare2Stack;
    protected static ?string $navigationLabel = 'Kategori Kamar';
    protected static ?string $label = 'Kategori Kamar';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Hotel';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('hotel', function ($query) {
                $query->where('owner_id', auth()->id());
            });
    }

    public static function form(Schema $schema): Schema
    {
        return RoomCategoryForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoomCategoryInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomCategoriesTable::configure($table);
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
            'index' => ListRoomCategories::route('/'),
            'create' => CreateRoomCategory::route('/create'),
            'view' => ViewRoomCategory::route('/{record}'),
            'edit' => EditRoomCategory::route('/{record}/edit'),
        ];
    }
}
