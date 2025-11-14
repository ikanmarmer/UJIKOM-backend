<?php

namespace App\Filament\Resepsionis\Resources\Bookings;

use App\Filament\Resepsionis\Resources\Bookings\Pages\CreateBooking;
use App\Filament\Resepsionis\Resources\Bookings\Pages\EditBooking;
use App\Filament\Resepsionis\Resources\Bookings\Pages\ListBookings;
use App\Filament\Resepsionis\Resources\Bookings\Pages\ViewBooking;
use App\Filament\Resepsionis\Resources\Bookings\Pages\CheckinPage;
use App\Filament\Resepsionis\Resources\Bookings\Pages\CheckoutPage;
use App\Filament\Resepsionis\Resources\Bookings\Schemas\BookingForm;
use App\Filament\Resepsionis\Resources\Bookings\Schemas\BookingInfolist;
use App\Filament\Resepsionis\Resources\Bookings\Tables\BookingsTable;
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
    protected static string|UnitEnum|null $navigationGroup = 'Manajemen Booking';
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->whereHas('hotel.staff', function ($query) {
                $query->where('user_id', auth()->id())
                    ->where('is_active', true);
            });
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getEloquentQuery()
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereDate('check_in_date', '>=', today())
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
            'checkin' => CheckinPage::route('/{record}/checkin'),
            'checkout' => CheckoutPage::route('/{record}/checkout'),
        ];
    }
}
