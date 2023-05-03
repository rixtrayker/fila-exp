<x-filament::page>


    <div class="flex text-sm" >
        {{-- Selectable Options --}}
        <div class="flex-1 border overflow-hidden rounded-lg shadow-sm bg-chartgreen text-chartgreenB"
             :class="{
            'bg-white border-gray-300': !@js(config('filament.dark_mode')),
            'dark:bg-gray-700 dark:border-gray-600': @js(config('filament.dark_mode'))
            }"
        >

            <p class='text-center py-4'>
                Done visits
            </p>
                @if(count($this->visited))
            <div
                class="p-2 bg-white text-chartgreenB">
                {{-- Search Input --}}


                <ul class="overflow-y-auto"
                    id="s_ms-two-sides_selectableOptions">

                    @foreach($this->visited as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartgreen hover:text-chartgreenB transition" >
                            {{$value->client?->name_en}}
                        </li>
                    @endforeach
                </ul>
            </div>
                @endif
        </div>

        {{-- Arrow Actions --}}
        <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
            <p>

            </p>
            <p>

            </p>
        </div>

        {{-- Selected Options --}}
        <div class="flex-1 border overflow-hidden rounded-lg shadow-sm  bg-chartblue text-chartblueB"
        >
            {{-- Title --}}
            <p class='text-center py-4 rounded-t-lg'> Pending Visits </p>
                @if(count($this->pending))
            <div  class="p-2 bg-white text-chartblueB">
                <ul class="overflow-y-auto"
                    id="122_ms-two-sides_selectedOptions">
                    @foreach($this->pending as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartblue hover:text-chartblueB transition" >
                            {{ $value->client?->name_en }}
                        </li>
                    @endforeach
                </ul>
            </div>
                @endif
        </div>

 {{-- Arrow Actions --}}
        <div class="justify-center flex flex-col px-2 space-y-2 translate-y-4">
            <p>

            </p>
            <p>

            </p>
        </div>

 <div class="flex-1 border overflow-hidden rounded-lg shadow-sm bg-chartred text-chartredB">
            {{-- Title --}}
            <p class='text-center py-4 rounded-t-lg '> Missed Visits </p>
            @if(count($this->missed))
            <div class="p-2 bg-white text-chartredB" >

                {{--  Options List --}}
                <ul class="overflow-y-auto"
                    id="122_ms-two-sides_selectedOptions">
                    @foreach($this->missed as $value)
                        <li class="cursor-pointer p-1 hover:bg-chartred hover:text-chartredB transition" >

                            {{ $value->client?->name_en }}
                        </li>
                    @endforeach
                </ul>
            </div>
                @endif
        </div>
    </div>
</x-filament::page>

