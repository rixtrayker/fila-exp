<x-filament-panels::page>
    @foreach($this->clients as $client)
        <livewire:frequency-report-cell :record="$client">
    @endforeach
</x-filament-panels::page>


