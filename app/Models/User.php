<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

use App\Traits\HasEditRequest;
use Filament\Models\Contracts\FilamentUser;
use Finller\Kpi\HasKpi;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Kalnoy\Nestedset\NodeTrait;
use App\Models\Role;
use Filament\Panel;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use \Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Helpers\DateHelper;
use App\Services\VacationCalculator;

class User extends Authenticatable  implements FilamentUser
{
    use NodeTrait;

    use \Staudenmeir\EloquentHasManyDeep\HasRelationships;
    use HasApiTokens;
    use HasFactory;
    use HasRoles;
    use HasProfilePhoto;
    use Notifiable;
    use SoftDeletes;
    use HasEditRequest;
    use TwoFactorAuthenticatable;
    use HasMergedRelationships;
    use HasKpi;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'parent_id',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];


    public function canAccessPanel(Panel $panel): bool
    {
        return true;
    }
    public function bricks()
    {
        return $this->belongsToMany(Brick::class);
    }
    public function areas()
    {
        return $this->belongsToMany(Area::class);
    }
    public function clientRequests()
    {
        return $this->hasMany(ClientRequest::class);
    }
    public function plans()
    {
        return $this->hasMany(Plan::class);
    }
    public function expenses()
    {
        return $this->hasMany(Expenses::class);
    }

    public function userMessages()
    {
        return $this->belongsToMany(Message::class);
    }
    public function roleMassges()
    {
        return $this->hasManyDeepFromRelations([$this, 'roles'], [new Role(), 'messages']);
    }

    public function allMessages()
    {
        return $this->mergedRelation('all_messages');
    }
    public function scopeSelectedVisible($query, $msg)
    {
        $pivot = $this->userMessages()->getTable();

        $query->whereHas('userMessages', function ($q) use ($pivot,$msg) {
            $q->where("{$pivot}.hidden", 0)->where("{$pivot}.message_id", $msg->id);
        });
    }
    public function sentMessages()
    {
        return $this->hasMany(Message::class);
    }
    public function manager()
    {
        return $this->belongsTo(User::class,'parent_id');
    }
    public function managedUsers()
    {
        return $this->hasMany(User::class, 'parent_id');
    }
    public function visits()
    {
        return $this->hasMany(Visit::class);
    }
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
    public function officeWorks()
    {
        return $this->hasMany(OfficeWork::class);
    }
    public function vacationRequests()
    {
        return $this->hasMany(VacationRequest::class);
    }

    public function vacationDurations()
    {
        return $this->hasManyDeepFromRelations([$this, 'vacationRequests'], [new VacationRequest(), 'vacationDurations']);
    }

    // clients within the same area and some range of distance
    // public function firstRole()
    // {
    //     return $this->morphToMany(
    //         config('permission.models.role'),
    //         'model',
    //         config('permission.table_names.model_has_roles'),
    //         config('permission.column_names.model_morph_key'),
    //         PermissionRegistrar::$pivotRole
    //     )->limit(1);
    // }

    public function scopeGetMine($builder)
    {
        if(auth()->user() && auth()->user()->hasRole(['medical-rep'])){
            return $builder->where('id', '=', auth()->id());
        }

        if(auth()->user() && auth()->user()->hasRole(['country-manager','area-manager','district-manager'])) {
            $ids = User::descendantsAndSelf(auth()->user())->pluck('id')->toArray();
            return $builder->whereIn('id', $ids);
        }

        return $builder;
    }

    /**
     * Get all users including inactive ones
     */
    public function scopeAllMine($builder)
    {
        return $builder->withoutGlobalScopes()->getMine();
    }

    /**
     * Get all users including inactive ones with a specific role
     */
    public function scopeAllWithRole($builder, $role)
    {
        return $builder->withoutGlobalScopes()->role($role);
    }

    /**
     * Get all users including inactive ones with specific roles
     */
    public function scopeAllWithRoles($builder, array $roles)
    {
        return $builder->withoutGlobalScopes()->role($roles);
    }

    // public function roles(): BelongsToMany
    // {
    //     $relation = $this->morphToMany(
    //         Role::class,
    //         'model',
    //         config('permission.table_names.model_has_roles'),
    //         config('permission.column_names.model_morph_key'),
    //         PermissionRegistrar::$pivotRole
    //     );

    //     if (! PermissionRegistrar::$teams) {
    //         return $relation;
    //     }

    //     return $relation->wherePivot(PermissionRegistrar::$teamsKey, getPermissionsTeamId())
    //         ->where(function ($q) {
    //             $teamField = config('permission.table_names.roles').'.'.PermissionRegistrar::$teamsKey;
    //             $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId());
    //         });
    // }

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('active', function (Builder $builder) {
            $builder->where('is_active', true);
        });

        static::updated(function ($model) {
            if( $model->isDirty('parent_id') ){
                self::fixTree();
            }
        });
    }

    public static function getDateRange()
    {
        return [
            request()->get('tableFilters.date_range.from_date') ?? today()->startOfMonth(),
            request()->get('tableFilters.date_range.to_date') ?? today()->endOfMonth()
        ];
    }
    // coverage report attributes vacations
    // area_name ( first area)
    public function getAreaNameAttribute()
    {
        return $this->areas()?->first()?->name;
    }
    // working days (calc_valid working days in that date range)

    // actual working days = working days - vacations

    // Actual visits = visits
    public function getActualVisitsAttribute()
    {
        $fromDate = self::getDateRange()[0];
        $toDate = self::getDateRange()[1];
        return $this->visits()->whereBetween('visit_date', [$fromDate, $toDate])->count();
    }
    // vacations (count vacations in that date range)
    public function getVacationsCountAttribute()
    {
        $fromDate = self::getDateRange()[0];
        $toDate = self::getDateRange()[1];

        return app(VacationCalculator::class)->calculateVacationDays($this, $fromDate, $toDate);
    }
    // working days
    public function getWorkingDaysAttribute()
    {
        $fromDate = self::getDateRange()[0];
        $toDate = self::getDateRange()[1];
        return DateHelper::countWorkingDays($fromDate, $toDate);
    }
    // actual working days
    public function getActualWorkingDaysAttribute()
    {
        $fromDate = self::getDateRange()[0];
        $toDate = self::getDateRange()[1];
        return DateHelper::countWorkingDays($fromDate, $toDate) - $this->vacations_count;
    }
    // Monthly visit target = actual working days * daily visit target
    // medical rep visit target from settings is cached
    public function getMonthlyVisitTargetAttribute()
    {
        $target = Setting::getSetting('medical_rep_visit_target')->value ?? 8;
        return $this->actual_working_days * $target;
    }


    // Daily report
    // public function getDailyReportCountAttribute()
    // {
    //     return $this->daily_reports()->count();
    // }

    // activities
    public function getActivitiesCountAttribute()
    {
        return $this->activities()->count();
    }
    // office work
    public function getOfficeWorkCountAttribute()
    {
        return $this->officeWorks()->count();
    }
    // SOPs = "{actual visits / monthly visits target * 100 } %" and rounded to 2 decimal places
    public function getSopsAttribute()
    {
        $dailyTarget = Setting::getSetting('medical_rep_visit_target')->value ?? 8;
        return round($this->actual_visits / ($this->actual_working_days * $dailyTarget) * 100, 2);
    }
    // Call rate = total visits / actual working days
    public function getCallRateAttribute()
    {
        $dailyTarget = Setting::getSetting('medical_rep_visit_target')->value ?? 8;
        return round($this->actual_visits / ($this->actual_working_days * $dailyTarget), 2);
    }
}