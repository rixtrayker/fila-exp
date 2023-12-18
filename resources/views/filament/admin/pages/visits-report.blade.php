<x-filament-panels::page>
    @foreach($this->visits as $visit)
        <livewire:visits-report-cell :record="$visit">
    @endforeach
</x-filament-panels::page>


