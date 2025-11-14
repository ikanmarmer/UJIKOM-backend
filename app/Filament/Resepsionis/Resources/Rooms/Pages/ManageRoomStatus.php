<?php

namespace App\Filament\Resepsionis\Resources\Rooms\Pages;

use App\Filament\Resepsionis\Resources\Rooms\RoomResource;
use App\Models\Room;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ManageRoomStatus extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = RoomResource::class;
    protected string $view = 'filament.resepsionis.pages.manage-room-status';

    public ?array $data = [];
    public Room $record;

    public function mount(Room $record): void
    {
        $this->record = $record;

        $this->form->fill([
            'current_status' => $record->status,
            'new_status' => $record->status,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Update Status Kamar')
                    ->description("Kamar {$this->record->room_number} - {$this->record->roomCategory->name}")
                    ->schema([
                        Select::make('current_status')
                            ->label('Status Saat Ini')
                            ->options([
                                'available' => 'Tersedia',
                                'occupied' => 'Terisi',
                                'maintenance' => 'Perawatan',
                                'cleaning' => 'Pembersihan',
                            ])
                            ->disabled()
                            ->native(false),

                        Select::make('new_status')
                            ->label('Status Baru')
                            ->options(function () {
                                // Can't change to occupied manually
                                $options = [
                                    'available' => 'Tersedia',
                                    'maintenance' => 'Perawatan',
                                    'cleaning' => 'Pembersihan',
                                ];

                                // Remove current status from options
                                unset($options[$this->record->status]);

                                return $options;
                            })
                            ->required()
                            ->native(false),

                        Textarea::make('status_notes')
                            ->label('Catatan')
                            ->placeholder('Alasan perubahan status, kondisi kamar, dll...')
                            ->rows(3),
                    ]),
            ])
            ->statePath('data');
    }

    public function updateStatus(): void
    {
        $data = $this->form->getState();

        // Prevent changing occupied status
        if ($this->record->status === 'occupied') {
            Notification::make()
                ->warning()
                ->title('Tidak Dapat Mengubah Status')
                ->body('Kamar yang sedang terisi tidak dapat diubah statusnya secara manual')
                ->send();
            return;
        }

        try {
            $this->record->update([
                'status' => $data['new_status'],
            ]);

            Notification::make()
                ->success()
                ->title('Status Berhasil Diubah')
                ->body("Status kamar {$this->record->room_number} telah diubah menjadi " . match ($data['new_status']) {
                    'available' => 'Tersedia',
                    'maintenance' => 'Perawatan',
                    'cleaning' => 'Pembersihan',
                    default => ucfirst($data['new_status']),
                })
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record]));

        } catch (\Exception $e) {
            \Log::error('Room status update failed: ' . $e->getMessage());

            Notification::make()
                ->danger()
                ->title('Update Gagal')
                ->body('Terjadi kesalahan saat mengubah status kamar')
                ->send();
        }
    }

    public function getFormActions(): array
    {
        return [
            \Filament\Actions\Action::make('update')
                ->label('Update Status')
                ->submit('updateStatus')
                ->color('primary'),

            \Filament\Actions\Action::make('cancel')
                ->label('Batal')
                ->color('gray')
                ->url(static::getResource()::getUrl('view', ['record' => $this->record])),
        ];
    }
}
