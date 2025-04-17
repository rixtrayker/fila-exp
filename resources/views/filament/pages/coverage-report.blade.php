<x-filament::page>


    <div class="flex text-sm" >

        <div class="flex-1 h-full border overflow-hidden rounded-lg shadow-sm text-chartgreenB @if(config('filament.dark_mode'))bg-chartgreen  border-gray-300 @endif">

            <p class='text-center py-4'>
                Done visits
            </p>
                {{-- @if(count($this->visited)) --}}
            <div
                class="p-2  @if(config('filament.dark_mode')) bg-white @endif text-chartgreenB">
                {{-- Search Input --}}

                <ul class="overflow-y-auto"
                    id="s_ms-two-sides_selectableOptions">
                    @foreach($this->visited as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartgreen hover:text-chartgreenB transition" style="justify-content:space-between;display:flex">
                            {{$value->client?->name_en}}
                            <span>
                            {{ $value->visit_date->format('d M y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
                {{-- @endif --}}
        </div>


        <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
            <p>

            </p>
            <p>

            </p>
        </div>


        <div class="flex-1 h-full border overflow-hidden rounded-lg shadow-sm text-chartblueB @if(config('filament.dark_mode')) bg-chartblue  border-gray-300 @endif"
        >
            {{-- Title --}}
            <p class='text-center py-4 rounded-t-lg'> Pending Visits </p>
                {{-- @if(count($this->pending)) --}}
            <div  class="p-2  @if(config('filament.dark_mode')) bg-white   @endif text-chartblueB">
                <ul class="overflow-y-auto"
                    id="122_ms-two-sides_selectedOptions">
                    @foreach($this->pending as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartblue hover:text-chartblueB" style="justify-content:space-between;display:flex" >
                            {{ $value->client?->name_en}}
                            <span>
                            {{ $value->visit_date->format('d M y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
                {{-- @endif --}}
        </div>


        <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
            <p>

            </p>
            <p>

            </p>
        </div>

        <div class="flex-1 h-full border overflow-hidden rounded-lg shadow-sm  text-chartredB @if(config('filament.dark_mode'))bg-chartred  border-gray-300 @endif">
            {{-- Title --}}
            <p class='text-center py-4 rounded-t-lg'> Missed Visits </p>
            {{-- @if(count($this->missed)) --}}
            <div class="p-2  @if(config('filament.dark_mode')) bg-white @endif text-chartredB" >

                {{--  Options List --}}
                <ul class="overflow-y-auto"
                    id="122_ms-two-sides_selectedOptions">
                    @foreach($this->missed as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartred hover:text-chartredB" style="justify-content:space-between;display:flex" >
                            {{ $value->client?->name_en}}
                            <span>
                            {{ $value->visit_date->format('d M y') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
                {{-- @endif --}}
        </div>
    </div>
{{--
    @push('scripts')

    <script>

    window.addEventListener('coverage-chart-update', function(event){
        if(event.detail.user_id)
            @this.set( 'user_id' , event.detail.user_id);
        if(event.detail)
            @this.set( 'from' , event.detail.from);
        if(event.detail.to)
            @this.set( 'to' , event.detail.to);
    });
    </script>
    @endpush --}}

</x-filament::page>

