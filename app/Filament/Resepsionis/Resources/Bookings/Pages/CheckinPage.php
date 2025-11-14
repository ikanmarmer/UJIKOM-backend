<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use App\Models\Booking;
use App\Models\Room;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class CheckinPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BookingResource::class;
    protected string $view = 'filament.resepsionis.pages.checkin';

    public ?array $data = [];
    public Booking $record;

    public function mount(Booking $record): void
    {
        $this->record = $record;

        // Load available rooms
        $availableRooms = $record->roomCategory->getAvailableRoomsForPeriod(
            $record->check_in_date,
            $record->check_out_date,
            $record->number_of_rooms
        );

        $this->form->fill([
            'booking_code' => $record->booking_code,
            'guest_name' => $record->guest_name,
            'check_in_date' => date('d M Y', strtotime($record->check_in_date)),
            'check_out_date' => date('d M Y', strtotime($record->check_out_date)),
            'number_of_rooms' => $record->number_of_rooms,
            'available_rooms' => $availableRooms->pluck('room_number', 'id')->toArray(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        $availableRooms = $this->record->roomCategory->getAvailableRoomsForPeriod(
            $this->record->check_in_date,
            $this->record->check_out_date,
            $this->record->number_of_rooms
        );

        return $schema
            ->schema([
                TextInput::make('booking_code')
                    ->label('Kode Booking')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('guest_name')
                    ->label('Nama Tamu')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('check_in_date')
                    ->label('Check-in')
                    ->disabled()
                    ->columnSpan(1),

                TextInput::make('check_out_date')
                    ->label('Check-out')
                    ->disabled()
                    ->columnSpan(1),

                Select::make('room_assignments')
                    ->label('Pilih Kamar')
                    ->options($availableRooms->pluck('room_number', 'id'))
                    ->multiple()
                    ->required()
                    ->minItems($this->record->number_of_rooms)
                    ->maxItems($this->record->number_of_rooms)
                    ->helperText("Pilih {$this->record->number_of_rooms} kamar untuk booking ini")
                    ->native(false)
                    ->columnSpanFull(),

                Textarea::make('checkin_notes')
                    ->label('Catatan Check-in')
                    ->placeholder('Catatan atau informasi tambahan saat check-in...')
                    ->rows(3)
                    ->columnSpanFull(),
            ])
            ->columns(2)
            ->statePath('data');
    }

    public function checkin(): void
    {
        $data = $this->form->getState();

        DB::beginTransaction();
        try {
            // Assign rooms
            foreach ($data['room_assignments'] as $roomId) {
                $this->record->rooms()->attach($roomId, [
                    'check_in_date' => $this->record->check_in_date,
                    'check_out_date' => $this->record->check_out_date,
                    'guests_count' => $this->record->guests_per_room,
                ]);

                // Update room status
                Room::find($roomId)->update(['status' => 'occupied']);
            }

            // Update booking status
            $this->record->update([
                'status' => 'active',
                'special_requests' => $this->record->special_requests
                    ? $this->record->special_requests . "\n\n**Check-in Notes:** " . ($data['checkin_notes'] ?? '')
                    : "**Check-in Notes:** " . ($data['checkin_notes'] ?? ''),
            ]);

            DB::commit();

            Notification::make()
                ->success()
                ->title('Check-in Berhasil')
                ->body("Tamu {$this->record->guest_name} telah check-in")
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Check-in failed: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Check-in Gagal')
                ->body('Terjadi kesalahan saat proses check-in')
                ->send();
        }
    }

    protected function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('checkin')
                ->label('Proses Check-in')
                ->submit('checkin')
                ->color('success'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
