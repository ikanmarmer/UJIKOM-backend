<?php

namespace App\Filament\Resepsionis\Resources\Bookings\Pages;

use App\Filament\Resepsionis\Resources\Bookings\BookingResource;
use App\Models\Booking;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\DB;

class CheckoutPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = BookingResource::class;
    protected string $view = 'filament.resepsionis.pages.checkout';

    public ?array $data = [];
    public Booking $record;

    public function mount(Booking $record): void
    {
        $this->record = $record;

        $this->form->fill([
            'booking_code' => $record->booking_code,
            'guest_name' => $record->guest_name,
            'check_in_date' => date('d M Y', strtotime($record->check_in_date)),
            'check_out_date' => date('d M Y', strtotime($record->check_out_date)),
            'total_price' => 'IDR ' . number_format((float)$record->total_price, 0, ',', '.'),
            'rooms_occupied' => $record->rooms->pluck('room_number')->join(', '),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Informasi Booking')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('booking_code')
                                    ->label('Kode Booking')
                                    ->disabled(),

                                TextInput::make('guest_name')
                                    ->label('Nama Tamu')
                                    ->disabled(),

                                TextInput::make('check_in_date')
                                    ->label('Check-in')
                                    ->disabled(),

                                TextInput::make('check_out_date')
                                    ->label('Check-out')
                                    ->disabled(),

                                TextInput::make('total_price')
                                    ->label('Total Pembayaran')
                                    ->disabled(),

                                TextInput::make('rooms_occupied')
                                    ->label('Kamar yang Digunakan')
                                    ->disabled(),
                            ]),
                    ]),

                Section::make('Konfirmasi Check-out')
                    ->schema([
                        Toggle::make('confirm_checkout')
                            ->label('Konfirmasi bahwa tamu telah check-out dan kamar dalam kondisi baik')
                            ->required()
                            ->accepted(),

                        Textarea::make('checkout_notes')
                            ->label('Catatan Check-out')
                            ->placeholder('Kondisi kamar, kerusakan, atau catatan lainnya...')
                            ->rows(3),

                        Toggle::make('set_cleaning')
                            ->label('Tandai kamar untuk pembersihan')
                            ->default(true)
                            ->helperText('Kamar akan ditandai sebagai "Pembersihan" setelah check-out'),
                    ]),
            ])
            ->statePath('data');
    }

    public function checkout(): void
    {
        $data = $this->form->getState();

        if (!($data['confirm_checkout'] ?? false)) {
            Notification::make()
                ->warning()
                ->title('Konfirmasi Diperlukan')
                ->body('Silakan konfirmasi check-out terlebih dahulu')
                ->send();
            return;
        }

        DB::beginTransaction();
        try {
            // Update room statuses
            $roomStatus = $data['set_cleaning'] ? 'cleaning' : 'available';
            foreach ($this->record->rooms as $room) {
                $room->update(['status' => $roomStatus]);
            }

            // Update booking status
            $this->record->update([
                'status' => 'completed',
                'special_requests' => $this->record->special_requests
                    ? $this->record->special_requests . "\n\nCheck-out Notes: " . ($data['checkout_notes'] ?? '')
                    : "Check-out Notes: " . ($data['checkout_notes'] ?? ''),
            ]);

            DB::commit();

            Notification::make()
                ->success()
                ->title('Check-out Berhasil')
                ->body("Tamu {$this->record->guest_name} telah check-out")
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Check-out failed: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Check-out Gagal')
                ->body('Terjadi kesalahan saat proses check-out')
                ->send();
        }
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('checkout')
                ->label('Proses Check-out')
                ->submit('checkout')
                ->color('success'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
