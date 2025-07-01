<?php

namespace App\Models\Reports;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Client;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FrequencyReportData extends Model
{
    use HasFactory;

    protected $table = 'frequency_report_data';

    protected $fillable = [
        'client_id',
        'report_date',
        'done_visits_count',
        'pending_visits_count',
        'missed_visits_count',
        'total_visits_count',
        'achievement_percentage',
        'metadata',
    ];

    protected $casts = [
        'report_date' => 'date',
        'achievement_percentage' => 'decimal:2',
        'metadata' => 'array',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get aggregated frequency report query
     * @param string $fromDate
     * @param string $toDate
     * @param array $filters
     * @return Builder
     */

    public static function getAggregatedQuery($fromDate, $toDate, $filters = []): Builder
    {
        DB::statement("SET SESSION sql_mode=''");

        // the grade is single value
        $grade = $filters['grade'] ?? null;
        // the client_type_id is array
        $clientTypeIds = $filters['client_type_id'] ?? null;
        // the brick_id is array
        $brickIds = $filters['brick_id'] ?? null;

        $query = static::query()
            ->select([
                'client_id',
                DB::raw('COALESCE(GROUP_CONCAT(DISTINCT clients.name_en), "") as client_name'),
                DB::raw('COALESCE(GROUP_CONCAT(DISTINCT client_types.name), "") as client_type_name'),
                DB::raw('COALESCE(GROUP_CONCAT(DISTINCT clients.grade), "") as grade'),
                DB::raw('COALESCE(GROUP_CONCAT(DISTINCT bricks.name), "") as brick_name'),
                // DB::raw('GROUP_CONCAT(DISTINCT bricks.area_id) as area_id'),
                DB::raw('COALESCE(SUM(done_visits_count), 0) as done_visits_count'),
                DB::raw('COALESCE(SUM(pending_visits_count), 0) as pending_visits_count'),
                DB::raw('COALESCE(SUM(missed_visits_count), 0) as missed_visits_count'),
                DB::raw('COALESCE(SUM(total_visits_count), 0) as total_visits_count'),
                DB::raw('CASE
                    WHEN COALESCE(SUM(total_visits_count), 0) > 0
                    THEN ROUND((COALESCE(SUM(done_visits_count), 0) / COALESCE(SUM(total_visits_count), 0)) * 100, 2)
                    ELSE 0.00
                END as achievement_percentage'),
            ])
            ->join('clients', 'frequency_report_data.client_id', '=', 'clients.id')
            ->join('client_types', 'clients.client_type_id', '=', 'client_types.id')
            ->join('bricks', 'clients.brick_id', '=', 'bricks.id')
            ->when($grade, function ($query) use ($grade) {
                $query->where('clients.grade', $grade);
            })
            ->when($clientTypeIds, function ($query) use ($clientTypeIds) {
                $query->whereIn('clients.client_type_id', $clientTypeIds);
            })
            ->when($brickIds, function ($query) use ($brickIds) {
                $query->whereIn('clients.brick_id', $brickIds);
            })
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->groupBy('client_id')
            ->with('client');

        return $query;
    }


    /**
     * Get aggregated frequency report data for a date range
     * @param string $fromDate
     * @param string $toDate
     * @param array $filters
     * @return Collection
     */
    public static function getAggregatedData($fromDate, $toDate, $filters = []): Collection
    {
        $query = self::getAggregatedQuery($fromDate, $toDate, $filters);
        return $query->get();
    }

    /**
     * Get data for a specific client and date range
     */
    public static function getClientData($clientId, $fromDate, $toDate)
    {
        return static::where('client_id', $clientId)
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->orderBy('report_date')
            ->get();
    }

    public static function createEmptyRecord($clientId, $date)
    {
        return static::updateOrCreate([
            'client_id' => $clientId,
            'report_date' => $date,
        ], [
            'done_visits_count' => 0,
            'pending_visits_count' => 0,
            'missed_visits_count' => 0,
            'total_visits_count' => 0,
            'achievement_percentage' => 0.00,
        ]);
    }
    /**
     * Create or update report data for a specific date
     */
    public static function updateOrCreateForDate($clientId, $date, $data)
    {
        return static::updateOrCreate(
            [
                'client_id' => $clientId,
                'report_date' => $date,
            ],
            $data
        );
    }

    /**
     * Get grade statistics for a date range
     */
    public static function getGradeStatistics($fromDate, $toDate)
    {
        return static::select([
                'clients.grade',
                DB::raw('SUM(missed_visits_count) as missed_visits'),
                DB::raw('SUM(total_visits_count) as total_visits'),
                DB::raw('CASE
                    WHEN SUM(total_visits_count) > 0
                    THEN ROUND((SUM(done_visits_count) / SUM(total_visits_count)) * 100, 2)
                    ELSE 0
                END as achievement_percentage'),
            ])
            ->join('clients', 'frequency_report_data.client_id', '=', 'clients.id')
            ->whereBetween('report_date', [$fromDate, $toDate])
            ->groupBy('grade')
            ->get()
            ->keyBy('grade');
    }

    /**
     * Clean old data (keep only last 30 days)
     */
    public static function cleanOldData()
    {
        $cutoffDate = Carbon::now()->subDays(30);
        return static::where('report_date', '<', $cutoffDate)->delete();
    }
}
