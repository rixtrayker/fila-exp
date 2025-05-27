<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Helpers\SortedStringSet;

class OfficialHoliday extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'date',
        'description',
        'country_id',
        'online',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'date' => 'date',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    // cache all official holidays in a set and update the cache on update or create
    public static function boot()
    {
        parent::boot();

        self::updated(function ($model) {
            self::cacheOfficialHolidays();
        });

        self::created(function ($model) {
            self::cacheOfficialHolidays();
        });
    }

    public static function cacheOfficialHolidays()
    {
        $cacheKey = 'official_holidays';

        $officialHolidays = self::query()
            ->selectRaw('date_format(date, "%Y-%m-%d") as date')
            ->get()
            ->pluck('date')
            ->toArray();


        $officialHolidaysSet = SortedStringSet::fromArray($officialHolidays);

        Cache::put($cacheKey, $officialHolidaysSet);
    }

    public static function getOfficialHolidaysInRange(Carbon $from, Carbon $to): array
    {
        $officialHolidaysSet = Cache::get('official_holidays');
        return $officialHolidaysSet->getElementsSorted($from, $to);
    }

    public static function getSetOfOfficialHolidaysInRange(Carbon $from, Carbon $to): SortedStringSet
    {
        $officialHolidaysSet = Cache::get('official_holidays');
        if(!$officialHolidaysSet){
            self::cacheOfficialHolidays();
            $officialHolidaysSet = Cache::get('official_holidays');
        }
        return $officialHolidaysSet->subset($from, $to);
    }
}
