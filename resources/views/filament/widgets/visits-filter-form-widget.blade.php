<x-filament::widget class="filament-widgets-chart-widget">
    <x-filament::card>
    <div class="mb-4">
        {{ $this->form }}
    </div>

    <div>
    <button wire:click="refreshData" type="submit" class="mt-4 mr-4 filament-button filament-button-size-md items-center justify-center py-1 gap-1 font-large rounded-lg border transition-colors outline-none active:ring-offset-2 active:ring-2 active:ring-inset dark:active:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow active:ring-white border-transparent bg-primary-600 hover:bg-primary-500 active:bg-primary-700 active:ring-offset-primary-700 ">
        Refresh
    </button>
    </div>

    </x-filament::card>
</x-filament::widget>
