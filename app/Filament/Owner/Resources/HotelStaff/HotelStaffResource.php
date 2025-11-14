<?php

namespace App\Filament\Owner\Resources\HotelStaff;

use App\Filament\Owner\Resources\HotelStaff\Pages\CreateHotelStaff;
use App\Filament\Owner\Resources\HotelStaff\Pages\EditHotelStaff;
use App\Filament\Owner\Resources\HotelStaff\Pages\ListHotelStaff;
use App\Filament\Owner\Resources\HotelStaff\Pages\ViewHotelStaff;
use App\Filament\Owner\Resources\HotelStaff\Schemas\HotelStaffForm;
use App\Filament\Owner\Resources\HotelStaff\Schemas\HotelStaffInfolist;
use App\Filament\Owner\Resources\HotelStaff\Tables\HotelStaffTable;
use App\Models\HotelStaff;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HotelStaffResource extends Resource
{
    protected static ?string $model = HotelStaff::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static ?string $navigationLabel = 'Manajemen Staf';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Hotel';
    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('hotel', function ($query) {
                $query->where('owner_id', auth()->id());
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()->where('is_active', true)->count();
    }

    public static function form(Schema $schema): Schema
    {
        return HotelStaffForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return HotelStaffInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HotelStaffTable::configure($table);
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
            'index' => ListHotelStaff::route('/'),
            'create' => CreateHotelStaff::route('/create'),
            'view' => ViewHotelStaff::route('/{record}'),
            'edit' => EditHotelStaff::route('/{record}/edit'),
        ];
    }
}
