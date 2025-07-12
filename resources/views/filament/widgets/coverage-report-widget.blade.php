<x-filament-widgets::widget>
    <x-filament::section>
        <div class="space-y-6">
            <!-- Type Selection -->
            <div class="flex justify-center gap-2">
                <button
                    wire:click="$set('selectedType', 'AM')"
                    class="px-6 py-2 rounded-lg font-medium transition-colors {{ $selectedType === 'AM' ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    AM
                </button>
                <button
                    wire:click="$set('selectedType', 'PM')"
                    class="px-6 py-2 rounded-lg font-medium transition-colors {{ $selectedType === 'PM' ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    PM
                </button>
                <button
                    wire:click="$set('selectedType', 'Pharmacy')"
                    class="px-6 py-2 rounded-lg font-medium transition-colors {{ $selectedType === 'Pharmacy' ? 'bg-primary-500 text-white' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
                >
                    Pharmacy
                </button>
            </div>

            <!-- Debug Info -->
            @php
                $stats = $this->getStats();
                $chartData = $this->getChartData();
            @endphp

            @if(count($chartData) === 0)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-4">
                    <div class="text-sm text-yellow-800 dark:text-yellow-200">
                        <strong>No chart data available for {{ $selectedType }} visits.</strong><br>
                        <span class="text-xs">
                            User ID: {{ auth()->id() }} |
                            Date Range: {{ now()->startOfMonth()->format('Y-m-d') }} to {{ now()->endOfMonth()->format('Y-m-d') }} |
                            Chart Data Points: {{ count($chartData) }}
                        </span>
                    </div>
                </div>
            @endif

            <!-- Stats Cards - Modern Responsive Layout -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-4">
                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 py-6">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Total Visits</div>
                    <div class="text-3xl font-extrabold text-gray-900 dark:text-gray-100">{{ $stats['total'] }}</div>
                </div>
                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 py-6">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Completed</div>
                    <div class="text-3xl font-extrabold text-green-600 dark:text-green-400">{{ $stats['completed'] }}</div>
                </div>
                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 py-6">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Pending</div>
                    <div class="text-3xl font-extrabold text-yellow-600 dark:text-yellow-400">{{ $stats['pending'] }}</div>
                </div>
                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 py-6">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Cancelled</div>
                    <div class="text-3xl font-extrabold text-red-600 dark:text-red-400">{{ $stats['cancelled'] }}</div>
                </div>
                <div class="flex flex-col items-center justify-center bg-white dark:bg-gray-800 rounded-xl shadow border dark:border-gray-700 py-6">
                    <div class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wide mb-1">Completion Rate</div>
                    <div class="text-3xl font-extrabold text-blue-600 dark:text-blue-400">{{ $stats['completion_rate'] }}%</div>
                </div>
            </div>

            <!-- Chart -->
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 shadow border dark:border-gray-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Coverage Report - {{ $selectedType }}</h3>
                <div class="h-96">
                    @php
                        $labels = collect($chartData)->pluck('date')->toArray();
                        $visitsData = collect($chartData)->pluck('visits')->toArray();
                        $completedData = collect($chartData)->pluck('completed')->toArray();
                        $pendingData = collect($chartData)->pluck('pending')->toArray();
                        $cancelledData = collect($chartData)->pluck('cancelled')->toArray();
                    @endphp

                    <canvas id="coverageChart-{{ $this->getId() }}" width="400" height="200"></canvas>
                </div>
            </div>
        </div>

        <!-- Include Chart.js if not already loaded -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        
        <script>
            document.addEventListener('livewire:init', function () {
                let chart = null;
                const chartId = 'coverageChart-{{ $this->getId() }}';

                function initChart() {
                    const canvas = document.getElementById(chartId);
                    if (!canvas) return;

                    const ctx = canvas.getContext('2d');

                    if (chart) {
                        chart.destroy();
                    }

                    chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($labels),
                            datasets: [
                                {
                                    label: 'Total Visits',
                                    data: @json($visitsData),
                                    borderColor: 'rgb(59, 130, 246)',
                                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                    tension: 0.1
                                },
                                {
                                    label: 'Completed',
                                    data: @json($completedData),
                                    borderColor: 'rgb(34, 197, 94)',
                                    backgroundColor: 'rgba(34, 197, 94, 0.1)',
                                    tension: 0.1
                                },
                                {
                                    label: 'Pending',
                                    data: @json($pendingData),
                                    borderColor: 'rgb(234, 179, 8)',
                                    backgroundColor: 'rgba(234, 179, 8, 0.1)',
                                    tension: 0.1
                                },
                                {
                                    label: 'Cancelled',
                                    data: @json($cancelledData),
                                    borderColor: 'rgb(239, 68, 68)',
                                    backgroundColor: 'rgba(239, 68, 68, 0.1)',
                                    tension: 0.1
                                }
                            ]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            scales: {
                                y: {
                                    beginAtZero: true
                                }
                            }
                        }
                    });
                }

                // Initialize chart after a short delay
                setTimeout(initChart, 100);

                // Listen for Livewire updates
                Livewire.on('chartDataUpdated', function () {
                    setTimeout(initChart, 100);
                });
            });
        </script>
    </x-filament::section>
</x-filament-widgets::widget>