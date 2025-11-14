{{-- resources/views/filament/resepsionis/pages/checkin.blade.php --}}
<x-filament-panels::page>
    <form wire:submit="checkin">
        {{ $this->form }}

        <div class="flex gap-3 justify-end mt-6">
            @foreach($this->getFormActions() as $action)
                {{ $action }}
            @endforeach
        </div>
    </form>
</x-filament-panels::page>
