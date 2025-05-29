<?php

namespace App\Http\Controllers;

use App\Jobs\SyncCoverageReportData;
use App\Jobs\SyncFrequencyReportData;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReportSyncController extends Controller
{
    /**
     * Run report sync based on type
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync(Request $request)
    {
        $type = $request->input('type', 'coverage');
        $fromDate = $request->input('from_date', today()->format('Y-m-d'));
        $toDate = $request->input('to_date', today()->format('Y-m-d'));

        if ($fromDate < Carbon::parse('2023-01-01')) {
            $fromDate = Carbon::parse('2023-01-01');
        }

        if ($toDate > today()) {
            $toDate = today();
        }

        try {
            if ($type === 'coverage') {
                $this->syncCoverageReport($fromDate, $toDate);
            } else if ($type === 'frequency') {
                $this->syncFrequencyReport($fromDate, $toDate);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid report type. Must be either "coverage" or "frequency"'
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => ucfirst($type) . ' report sync started successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Report sync failed', [
                'type' => $type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to start report sync'
            ], 500);
        }
    }

    /**
     * Sync coverage report for date range
     *
     * @param string $fromDate
     * @param string $toDate
     * @return void
     */
    private function syncCoverageReport(string $fromDate, string $toDate): void
    {
        $startDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($toDate);

        SyncCoverageReportData::dispatch($startDate, $endDate);
    }

    /**
     * Sync frequency report for date range
     *
     * @param string $fromDate
     * @param string $toDate
     * @return void
     */
    private function syncFrequencyReport(string $fromDate, string $toDate): void
    {
        $startDate = Carbon::parse($fromDate);
        $endDate = Carbon::parse($toDate);

        SyncFrequencyReportData::dispatch($startDate, $endDate);
    }
}
