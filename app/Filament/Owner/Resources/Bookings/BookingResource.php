<?php

namespace App\Filament\Owner\Resources\Bookings;

use App\Filament\Owner\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Owner\Resources\Bookings\Pages\EditBooking;
use App\Filament\Owner\Resources\Bookings\Pages\ListBookings;
use App\Filament\Owner\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Owner\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Owner\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Owner\Resources\Bookings\Tables\BookingsTable;
use App\Models\Booking;
use BackedEnum;
use UnitEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class BookingResource extends Resource
{
    protected static ?string $model = Booking::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendar;
    protected static ?string $navigationLabel = 'Booking';
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Pemesanan';
    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('hotel', function ($query) {
                $query->where('owner_id', auth()->id());
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();
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
        return BookingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BookingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BookingsTable::configure($table);
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
            'index' => ListBookings::route('/'),
            'create' => CreateBooking::route('/create'),
            'view' => ViewBooking::route('/{record}'),
            'edit' => EditBooking::route('/{record}/edit'),
        ];
    }
}
