<x-filament-panels::page>
    {{-- Page Header with Stats --}}
    <div class="mb-8">
        @php
            $stats = $this->getStats();
        @endphp
        
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">Visit Statistics</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-6">
                    {{-- Total Visits --}}
                    <div class="text-center">
                        <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-s-calendar-days class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Total Visits</div>
                        <div class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ number_format($stats['total_visits']) }}</div>
                    </div>

                    {{-- Visited --}}
                    <div class="text-center">
                        <div class="w-12 h-12 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-s-check-circle class="w-6 h-6 text-green-600 dark:text-green-400" />
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Visited</div>
                        <div class="text-2xl font-bold text-green-600 dark:text-green-400">{{ number_format($stats['visited']) }}</div>
                        @if($stats['total_visits'] > 0)
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ round(($stats['visited'] / $stats['total_visits']) * 100, 1) }}%
                            </div>
                        @endif
                    </div>

                    {{-- Pending --}}
                    <div class="text-center">
                        <div class="w-12 h-12 bg-yellow-100 dark:bg-yellow-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-s-clock class="w-6 h-6 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Pending</div>
                        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">{{ number_format($stats['pending']) }}</div>
                        @if($stats['total_visits'] > 0)
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ round(($stats['pending'] / $stats['total_visits']) * 100, 1) }}%
                            </div>
                        @endif
                    </div>

                    {{-- Missed --}}
                    <div class="text-center">
                        <div class="w-12 h-12 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-s-x-circle class="w-6 h-6 text-red-600 dark:text-red-400" />
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Missed</div>
                        <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ number_format($stats['missed']) }}</div>
                        @if($stats['total_visits'] > 0)
                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                {{ round(($stats['missed'] / $stats['total_visits']) * 100, 1) }}%
                            </div>
                        @endif
                    </div>

                    {{-- Unique Clients --}}
                    <div class="text-center">
                        <div class="w-12 h-12 bg-purple-100 dark:bg-purple-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <x-heroicon-s-building-office-2 class="w-6 h-6 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-1">Unique Clients</div>
                        <div class="text-2xl font-bold text-purple-600 dark:text-purple-400">{{ number_format($stats['unique_clients']) }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Client Breakdown Section --}}
    {{-- @if($stats['total_visits'] > 0)
        <div class="mb-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-s-chart-bar class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                        &nbsp;Top Clients by Visit Count
                    </h3>
                </div>
                <div class="p-6">
                    @php
                        $clientBreakdown = $this->getClientBreakdown();
                    @endphp
                    
                    @if(count($clientBreakdown) > 0)
                        <div class="space-y-4">
                            @foreach($clientBreakdown as $clientData)
                                <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600">
                                    <div class="flex items-center space-x-4">
                                        <div class="w-10 h-10 bg-primary-100 dark:bg-primary-900/50 rounded-full flex items-center justify-center">
                                            <span class="text-primary-600 dark:text-primary-400 font-semibold text-sm">
                                                {{ substr($clientData['client']->name_en ?? 'U', 0, 2) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="font-semibold text-gray-900 dark:text-gray-100">
                                                {{ $clientData['client']->name_en ?? 'Unknown Client' }}
                                            </h4>
                                            <p class="text-sm text-gray-500 dark:text-gray-400">
                                                Total: {{ $clientData['total'] }} {{ Str::plural('visit', $clientData['total']) }}
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-center space-x-3">
                                        @if($clientData['visited'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                                                <x-heroicon-s-check-circle class="w-3 h-3 mr-1" />
                                                {{ $clientData['visited'] }}
                                            </span>
                                        @endif
                                        
                                        @if($clientData['pending'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300">
                                                <x-heroicon-s-clock class="w-3 h-3 mr-1" />
                                                {{ $clientData['pending'] }}
                                            </span>
                                        @endif
                                        
                                        @if($clientData['missed'] > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300">
                                                <x-heroicon-s-x-circle class="w-3 h-3 mr-1" />
                                                {{ $clientData['missed'] }}
                                            </span>
                                        @endif
                                        
                                        <a href="{{ route('filament.admin.resources.clients.view', $clientData['client']->id) }}"
                                           target="_blank"
                                           class="inline-flex items-center px-3 py-1 text-xs font-medium text-primary-700 bg-primary-50 hover:bg-primary-100 dark:bg-primary-900/50 dark:text-primary-300 dark:hover:bg-primary-900 rounded-md transition-colors">
                                            View Client
                                            <x-heroicon-s-arrow-top-right-on-square class="w-3 h-3 ml-1" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <x-heroicon-o-chart-bar class="w-12 h-12 mx-auto text-gray-400 dark:text-gray-500 mb-4" />
                            <p class="text-gray-500 dark:text-gray-400">No client breakdown data available</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif --}}

    {{-- Main Table --}}
    <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                <x-heroicon-s-table-cells class="w-5 h-5 mr-2 text-gray-600 dark:text-gray-400" />
                 &nbsp;{{ $this->breakdownStrategy->getTableHeading() }}
            </h3>
            {{-- <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Comprehensive view of all visits with advanced filtering and sorting options
            </p> --}}
        </div>
        <div class="p-0">
            {{ $this->table }}
        </div>
    </div>
</x-filament-panels::page>