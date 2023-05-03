<x-filament::widget class="filament-widgets-chart-widget">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <x-filament::card>

        <select name="vv" class="select2" id="vv" onchange="up()"
        class="text-gray-900 block w-full transition duration-75 rounded-lg shadow-sm outline-none focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70
        @if(config('forms.dark_mode')) dark:bg-gray-700 dark:text-white dark:focus:border-primary-500'][config('forms.dark_mode')] @endif"
        >
        <option value="2021-09-01">21e</option>
        <option value="2023-01-01">fdsfcs</option>
        <option value="2023-01-04">fdaf</option>
    </select>
    </x-filament::card>

{{-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script> --}}
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function up(){
        document.getElementById('vv').select2();
        const params = new URLSearchParams(window.location.search);
        params.set('from',document.getElementById('vv').value );
        params.set('ids',{{auth()->id()}} );
        const newUrl = window.location.pathname + '?' + params.toString();
        window.history.pushState({path:newUrl},'',newUrl);

        const myEvent = new Event('coverage-chart-update');
        window.dispatchEvent(myEvent);
    }
    </script>
</x-filament::widget>
