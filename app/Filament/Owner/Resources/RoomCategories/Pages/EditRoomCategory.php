<?php

namespace App\Filament\Owner\Resources\RoomCategories\Pages;

use App\Filament\Owner\Resources\RoomCategories\RoomCategoryResource;
use App\Models\Room;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditRoomCategory extends EditRecord
{
    protected static string $resource = RoomCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete Room Category')
                ->modalDescription('Are you sure? All rooms in this category will also be deleted.'),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing rooms for the repeater
        $data['existing_rooms'] = $this->record->rooms()
            ->orderBy('floor')
            ->orderBy('room_number')
            ->get()
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Clean up additional fields
        if (isset($data['add_rooms_count'])) {
            unset($data['add_rooms_count']);
        }

        if (isset($data['new_room_start_number'])) {
            unset($data['new_room_start_number']);
        }

        if (isset($data['existing_rooms'])) {
            unset($data['existing_rooms']);
        }

        return $data;
    }

    protected function afterSave(): void
    {
        $data = $this->form->getRawState();
        $category = $this->record;

        // Handle adding more rooms
        if (isset($data['add_rooms_count']) && $data['add_rooms_count'] > 0) {
            $startNumber = $data['new_room_start_number'] ?? 101;
            $count = $data['add_rooms_count'];

            for ($i = 0; $i < $count; $i++) {
                $roomNumber = ($startNumber + $i);

                // Avoid duplicates
                $exists = Room::where('room_category_id', $category->id)
                    ->where('room_number', $roomNumber)
                    ->exists();

                if (!$exists) {
                    Room::create([
                        'room_category_id' => $category->id,
                        'room_number' => $roomNumber,
                        'floor' => '1', // Default floor
                        'status' => 'available',
                    ]);
                }
            }

            Notification::make()
                ->success()
                ->title('Rooms added successfully')
                ->body("Added {$count} new rooms")
                ->send();
        }

        // Update total rooms count
        $category->refresh();
        $category->updateTotalRooms();
    }
}
