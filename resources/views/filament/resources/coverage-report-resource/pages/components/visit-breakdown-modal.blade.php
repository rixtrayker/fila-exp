<div class="space-y-4">
    @php
        $visits = $user->visits()
            ->whereBetween('visit_date', [$fromDate, $toDate])
            ->when($status, function ($query) use ($status) {
                return $query->whereIn('status', $status);
            })
            ->with('client')
            ->orderBy('visit_date', 'desc')
            ->get()
            ->groupBy('client_id');
    @endphp

    @foreach($visits as $clientId => $clientVisits)
        @php
            $clientId = $clientId ?? 0;
        @endphp
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-2">
                    <a href="{{ route('filament.admin.resources.clients.view', ['record' => $clientId]) }}" target="_blank"
                       class="font-medium text-gray-900 dark:text-gray-100 hover:text-primary-500 dark:hover:text-primary-400">
                        {{ $clientVisits->first()?->client?->name ?? 'Unknown Client' }}
                    </a>
                    <span class="text-sm text-gray-500 dark:text-gray-400">
                        ({{ $clientVisits->count() }} visits)
                    </span>
                </div>
            </div>

            <div class="mt-2 space-y-1">
                @foreach($clientVisits as $visit)
                    <a href="{{ route('filament.admin.resources.visits.view', ['record' => $visit->id]) }}" target="_blank"
                       class="block text-sm text-gray-600 dark:text-gray-300 hover:text-primary-500 dark:hover:text-primary-400">
                        {{ $visit->visit_date->format('Y-m-d') }} -
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                            @if($visit->status === 'visited') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                            @elseif($visit->status === 'pending') bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200
                            @elseif($visit->status === 'missed') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                            @else bg-gray-100 text-gray-800 dark:bg-gray-100 dark:text-white @endif">
                            {{ ucfirst($visit->status) }}
                        </span>
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('visitBreakdown', () => ({
            init() {
                // Initialize any Alpine.js functionality here if needed
            }
        }))
    })
</script>
@endpush
