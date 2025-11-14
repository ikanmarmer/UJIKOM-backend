<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Widgets --}}
        <x-filament-widgets::widgets
            :widgets="$this->getWidgets()"
            :columns="$this->getColumns()"
        />
    </div>
</x-filament-panels::page>
