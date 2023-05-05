<x-filament::widget class="filament-widgets-chart-widget">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <x-filament::card>
        {{-- <div>
            <label for="vv">Medical Reps</label>
            <select name="vv" id="vv" onchange="up()"
            class="select2 text-gray-900 block w-full transition duration-75 rounded-lg shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70
            @if(config('forms.dark_mode')) dark:bg-gray-700 dark:text-white dark:focus:border-primary-500'][config('forms.dark_mode')] @endif"
            multiple >
                <option value="2021-09-01">21e</option>
                <option value="2023-01-01">fdsfcs</option>
                <option value="2023-01-04">fdaf</option>
            </select>
    </div> --}}
    <div>
        {{ $this->form }}
    </div>
    <div>

    <button onclick="refreshData()" type="submit" class="mr-4 filament-button filament-button-size-md items-center justify-center py-1 gap-1 font-large rounded-lg border transition-colors outline-none active:ring-offset-2 active:ring-2 active:ring-inset dark:active:ring-offset-0 min-h-[2.25rem] px-4 text-sm text-white shadow active:ring-white border-transparent bg-primary-600 hover:bg-primary-500 active:bg-primary-700 active:ring-offset-primary-700 ">
        Refresh
    </button>
    </div>

    </x-filament::card>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
{{-- <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script> --}}

<script>
    function refreshData(){
        Livewire.emit('updateVisitsList',$('#from').val(),$('#to').val(),$('#user_id').val());
        window.dispatchEvent(new CustomEvent('coverage-chart-update',{ "detail": {"user_id":$('#user_id').val(), "from":$('#from').val(),"to":$('#to').val() } }));
    }
</script>

</x-filament::widget>
