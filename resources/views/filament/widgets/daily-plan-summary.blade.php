<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">
                    Today's Daily Plan Summary
                </h3>
                <div class="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                    <span>{{ $this->getTotalBricks() }} Bricks</span>
                    <span>•</span>
                    <span>{{ $this->getTotalClients() }} Clients</span>
                </div>
            </div>

            @php
                $planData = $this->getDailyPlan();
            @endphp

            <!-- Content Container with Fixed Height and Scrolling -->
            <div class="h-96 overflow-y-auto">
                @if(empty($planData))
                    <div class="flex items-center justify-center h-full">
                        <div class="text-center text-gray-500 dark:text-gray-400">
                            <svg class="mx-auto h-12 w-12 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            <p class="text-lg font-medium">No planned visits for today</p>
                            <p class="text-sm">Your daily plan is empty or all visits are completed.</p>
                        </div>
                    </div>
                @else
                    <!-- Bricks Grid with Scrollable Content -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 pb-4">
                        @foreach($planData as $brick)
                            <div class="bg-white dark:bg-gray-800 rounded-lg border dark:border-gray-700 p-4 h-fit">
                                <!-- Brick Header -->
                                <div class="mb-3">
                                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 truncate" title="{{ $brick['brick_name'] }}">
                                        {{ $brick['brick_name'] }}
                                    </h4>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">
                                        {{ $brick['total_clients'] }} client{{ $brick['total_clients'] > 1 ? 's' : '' }}
                                    </p>
                                </div>

                                <!-- Client Types -->
                                <div class="space-y-2">
                                    @foreach(['AM', 'PM', 'PH'] as $type)
                                        @if(!empty($brick['clients'][$type]))
                                            <div class="flex items-center justify-between">
                                                <span class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                    {{ $type === 'AM' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : '' }}
                                                    {{ $type === 'PM' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : '' }}
                                                    {{ $type === 'PH' ? 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200' : '' }}
                                                ">
                                                    {{ $type === 'PH' ? 'Pharmacy' : $type }}
                                                </span>
                                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                    {{ count($brick['clients'][$type]) }}
                                                </span>
                                            </div>
                                            
                                            <!-- Client Names (collapsible) -->
                                            <div class="ml-2 text-xs text-gray-600 dark:text-gray-400">
                                                @foreach($brick['clients'][$type] as $index => $client)
                                                    @if($index < 3)
                                                        <div class="truncate" title="{{ $client['name'] }}">
                                                            • {{ $client['name'] }}
                                                        </div>
                                                    @elseif($index === 3)
                                                        <div class="text-gray-500 dark:text-gray-500">
                                                            ... and {{ count($brick['clients'][$type]) - 3 }} more
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>