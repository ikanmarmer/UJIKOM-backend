{{-- resources/views/filament/resepsionis/pages/manage-room-status.blade.php --}}
<x-filament-panels::page>
    <form wire:submit="updateStatus">
        {{ $this->form }}

        <div class="flex gap-3 justify-end mt-6">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
