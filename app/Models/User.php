<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Traits\HasRoles;
use \Staudenmeir\LaravelMergedRelations\Eloquent\HasMergedRelationships;

class User extends Authenticatable implements FilamentUser
{
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
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
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
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    public function canAccessFilament(): bool
    {
        return true;
    }

    public function cities()
    {
        return $this->belongsToMany(City::class);
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
        return $this->belongsTo(User::class,'manager_id');
    }
    public function firstRole()
    {
        return $this->morphToMany(
            config('permission.models.role'),
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            PermissionRegistrar::$pivotRole
        )->limit(1);
    }

    public function scopeMine( $query){
        $column_name = 'id';
        if(auth()->user() && auth()->user()->hasRole('medical-rep')){
            $query->where($column_name, '=', auth()->id());
        }

        if(auth()->user() && auth()->user()->hasRole(['country-manager','area-manager','district-manager'])) {
            $query = DB::table('users')
            ->selectRaw('id')
                ->where('manager_id',auth()->id())
                ->unionAll(
                    DB::table('users')
                        ->select('users.id')
                        ->join('tree', 'tree.id', '=', 'users.manager_id')
                );

            $tree = DB::table('tree')
                ->withRecursiveExpression('tree', $query)
                ->pluck('id')->toArray();

            $tree[] = auth()->id();
            $query->whereIn($column_name, $tree);
        }
    }

    public function roles(): BelongsToMany
    {
        $relation = $this->morphToMany(
            Role::class,
            'model',
            config('permission.table_names.model_has_roles'),
            config('permission.column_names.model_morph_key'),
            PermissionRegistrar::$pivotRole
        );

        if (! PermissionRegistrar::$teams) {
            return $relation;
        }

        return $relation->wherePivot(PermissionRegistrar::$teamsKey, getPermissionsTeamId())
            ->where(function ($q) {
                $teamField = config('permission.table_names.roles').'.'.PermissionRegistrar::$teamsKey;
                $q->whereNull($teamField)->orWhere($teamField, getPermissionsTeamId());
            });
    }
}
