<?php

namespace App\Filament\Resepsionis\Resources\Rooms;

use App\Filament\Resepsionis\Resources\Rooms\Pages\ListRooms;
use App\Filament\Resepsionis\Resources\Rooms\Pages\ViewRoom;
use App\Filament\Resepsionis\Resources\Rooms\Pages\ManageRoomStatus;
use App\Filament\Resepsionis\Resources\Rooms\Schemas\RoomForm;
use App\Filament\Resepsionis\Resources\Rooms\Schemas\RoomInfolist;
use App\Filament\Resepsionis\Resources\Rooms\Tables\RoomsTable;
use App\Models\Room;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RoomResource extends Resource
{
    protected static ?string $model = Room::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHome;
    protected static ?string $navigationLabel = 'Manajemen Kamar';
    protected static ?string $label = 'Kamar';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Hotel';
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('roomCategory.hotel.staff', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('is_active', true);
            })
            ->with(['roomCategory.hotel']);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereIn('status', ['maintenance', 'cleaning'])
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return RoomForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return RoomInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RoomsTable::configure($table);
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
            'index' => ListRooms::route('/'),
            // 'create' => CreateRoom::route('/create'),
            'view' => ViewRoom::route('/{record}'),
            // 'edit' => EditRoom::route('/{record}/edit'),
            'manage-status' => ManageRoomStatus::route('/{record}/status'),
        ];
    }
}
