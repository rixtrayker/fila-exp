<?php

namespace App\Models\Reports;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CoverageReportData extends Model
{
    use HasFactory;

    protected $table = 'coverage_report_data';

    protected $fillable = [
        'user_id',
        'date',
        'done_visits_count',
        'pending_visits_count',
        'missed_visits_count',
        'total_visits_count',
        'achievement_percentage',
        'is_final',
    ];

    protected $casts = [
        'date' => 'date',
        'is_final' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function updateOrCreateForDate(int $userId, string $date, array $data)
    {
        return self::updateOrCreate(
            [
                'user_id' => $userId,
                'date' => $date,
            ],
            $data
        );
    }
}