<div class="max-h-96 overflow-y-auto">
    @if($visitData->isEmpty())
        <div class="text-center py-8">
            <div class="text-gray-400 dark:text-gray-500 mb-2">
                <x-heroicon-o-calendar-days class="w-12 h-12 mx-auto" />
            </div>
            <p class="text-gray-500 dark:text-gray-400">No visits found for the selected criteria.</p>
        </div>
    @else
        <div class="grid gap-4">
            @foreach($visitData as $clientId => $clientVisits)
                @php
                    $client = $clientVisits->first()?->client;
                    $visitCount = $clientVisits->count();
                    $statusCounts = $clientVisits->countBy('status');
                @endphp

                <div class="bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                    <!-- Client Header -->
                    <div class="p-4 border-b border-gray-200 dark:border-gray-600">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-3">
                                <div class="flex-shrink-0">
                                    <div class="w-8 h-8 bg-primary-100 dark:bg-primary-900 rounded-full flex items-center justify-center">
                                        <span class="text-primary-600 dark:text-primary-400 font-medium text-sm">
                                            {{ substr($client?->name ?? 'U', 0, 1) }}
                                        </span>
                                    </div>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-900 dark:text-gray-100">
                                        {{ $client?->name ?? 'Unknown Client' }}
                                    </h3>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">
                                        {{ $visitCount }} {{ Str::plural('visit', $visitCount) }}
                                    </p>
                                </div>
                            </div>

                            <!-- Status Summary -->
                            <div class="flex space-x-2">
                                @foreach($statusCounts as $status => $count)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                        @switch($status)
                                            @case('visited')
                                                bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300
                                                @break
                                            @case('pending')
                                                bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300
                                                @break
                                            @case('missed')
                                                bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300
                                                @break
                                            @default
                                                bg-gray-100 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300
                                        @endswitch">
                                        {{ $count }} {{ ucfirst($status) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <!-- Visit List -->
                    <div class="p-4">
                        <div class="space-y-2">
                            @foreach($clientVisits as $visit)
                                <div class="flex items-center justify-between py-2 px-3 bg-white dark:bg-gray-800 rounded border border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-700 transition-colors">
                                    <div class="flex items-center space-x-3">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $visit->visit_date->format('M j, Y') }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $visit->visit_date->format('l') }}
                                        </div>
                                    </div>

                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                            @switch($visit->status)
                                                @case('visited')
                                                    bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300
                                                    @break
                                                @case('pending')
                                                    bg-yellow-100 text-yellow-700 dark:bg-yellow-900/50 dark:text-yellow-300
                                                    @break
                                                @case('missed')
                                                    bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300
                                                    @break
                                                @default
                                                    bg-gray-100 text-gray-700 dark:bg-gray-900/50 dark:text-gray-300
                                            @endswitch">
                                            {{ ucfirst($visit->status) }}
                                        </span>

                                        <a href="{{ route('filament.admin.resources.visits.view', $visit->id) }}"
                                           target="_blank"
                                           class="text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors">
                                            <x-heroicon-o-eye class="w-4 h-4" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Action Buttons -->
<div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-600">
    <div class="flex justify-between items-center">
        <div class="text-sm text-gray-500 dark:text-gray-400">
            Showing visits from {{ $fromDate}}
            to {{ $toDate}}
        </div>

        @if($client ?? false)
            <a href="{{ route('filament.admin.resources.clients.view', $client->id) }}"
               target="_blank"
               class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-primary-700 bg-primary-100 hover:bg-primary-200 dark:bg-primary-900/50 dark:text-primary-300 dark:hover:bg-primary-900 transition-colors">
                <x-heroicon-o-building-office class="w-4 h-4 mr-2" />
                View Client Details
            </a>
        @endif
    </div>
</div>?>