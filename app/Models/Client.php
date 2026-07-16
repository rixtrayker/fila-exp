<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model
{
    use HasEditRequest;
    use HasFactory;
    use HasRelationships;

    protected $appends = ['name', 'mapUrl'];

    protected $with = ['brick.area'];

    protected $fillable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        'location',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
        'lat',
        'lng',
        'active',
    ];

    public $editable = [
        'name_en',
        'name_ar',
        'email',
        'phone',
        'address',
        // 'location',
        'brick_id',
        'grade',
        'shift',
        'related_pharmacy',
        'am_work',
        'client_type_id',
        'speciality_id',
        'lat',
        'lng',
        'active',
    ];

    protected static function booted()
    {
        static::addGlobalScope('active', function ($query) {
            $query->where('active', true);
        });
    }

    public function activate()
    {
        $this->update(['active' => true]);
    }

    public function deactivate()
    {
        $this->update(['active' => false]);
    }

    public function scopeWithInactive($query)
    {
        return $query->withoutGlobalScope('active');
    }

    public function scopeOnlyInactive($query)
    {
        return $query->withoutGlobalScope('active')->where('active', false);
    }

    public function setLocationAttribute($value)
    {
        $this->attributes['location'] = json_encode($value);
    }

    public function visits()
    {
        return $this->hasMany(Visit::class);
    }

    public function visitedBy()
    {
        return $this->hasManyDeepFromRelations(
            $this->visits(),
            (new Visit())->user()
        );
    }

    public function brick()
    {
        return $this->belongsTo(Brick::class);
    }

    public function clientType()
    {
        return $this->belongsTo(ClientType::class);
    }

    public function speciality()
    {
        return $this->belongsTo(Speciality::class);
    }

    public function getNameAttribute()
    {
        return $this->name_en.' - '.$this->name_ar;
    }

    public function mapUrl(): ?string
    {
        if (! $this->lat || ! $this->lng) {
            return null;
        }

        return 'https://www.google.com/maps/place/'.
            $this->lat.
            ','.
            $this->lng;
    }

    public function getMapUrlAttribute(): ?string
    {
        return $this->mapUrl();
    }

    public function setLocation($value)
    {
        $this->lat = $value['lat'];
        $this->lng = $value['lng'];
        $this->location = $value;
        $this->save();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->whereJsonContains('name', $search);
        });
        // ->when($filters['status'] ?? null, function ($query, $status) {
        //     $query->where('status', '=', $status);
        // });
    }

    public function clientRequests()
    {
        return $this->hasMany(ClientRequest::class);
    }

    // pharmacy filter
    public function scopePharmacy($query)
    {
        return $query->where('client_type_id', ClientType::PH);
    }

    public function scopeInMyAreas($builder)
    {
        if (! self::isAuthenticated()) {
            return;
        }

        if (self::isSuperAdmin()) {
            return $builder;
        }

        $brickIds = self::getMyBricksIds();

        return $builder->whereIn('brick_id', $brickIds);
    }

    /**
     * Users who have added this client to their personal client list.
     */
    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    /**
     * Scope: clients on the authenticated user's personal client list.
     */
    public function scopeInMyList($builder)
    {
        if (! self::isAuthenticated()) {
            return;
        }

        return self::applyPersonalListConstraint($builder, auth()->id());
    }

    /**
     * Scope: the "accountable pool" of clients for a user.
     *
     * This is the fallback rule used by coverage reporting:
     * - When the user maintains a personal client list (client_user rows),
     *   the pool is exactly that list.
     * - When the user has no personal list, the pool falls back to the
     *   shared area-derived client base (all clients in the user's bricks,
     *   as resolved by user_bricks_view — same source as scopeInMyAreas).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  int|null  $userId  Defaults to the authenticated user.
     */
    public function scopeAccountablePool($builder, int $userId = null, int $clientTypeId = null)
    {
        $userId = $userId ?? auth()->id();

        if (! $userId) {
            return;
        }

        $builder->whereIn(
            'brick_id',
            UserBricksView::getUserBrickIds($userId),
        );

        if (self::userHasPersonalList($userId, $clientTypeId)) {
            return self::applyPersonalListConstraint($builder, $userId);
        }

        return $builder;
    }

    /**
     * Whether the given user maintains a personal client list.
     */
    public static function userHasPersonalList(int $userId, int $clientTypeId = null): bool
    {
        return DB::table('client_user')
            ->join('clients as personal_clients', 'personal_clients.id', '=', 'client_user.client_id')
            ->join('user_bricks_view as personal_bricks', function ($join) {
                $join
                    ->on('personal_bricks.user_id', '=', 'client_user.user_id')
                    ->on('personal_bricks.brick_id', '=', 'personal_clients.brick_id');
            })
            ->where('client_user.user_id', $userId)
            ->where('personal_clients.active', true)
            ->when(
                $clientTypeId,
                fn ($query) => $query->where('personal_clients.client_type_id', $clientTypeId),
            )
            ->exists();
    }

    /**
     * Constrain the query to clients on the given user's personal list.
     */
    private static function applyPersonalListConstraint($builder, int $userId)
    {
        return $builder->whereExists(function ($query) use ($userId) {
            $query
                ->select(DB::raw(1))
                ->from('client_user')
                ->whereColumn('client_user.client_id', 'clients.id')
                ->where('client_user.user_id', $userId);
        });
    }

    private static function isAuthenticated(): bool
    {
        return auth()->check();
    }

    private static function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole('super-admin');
    }

    public static function getMyBricksIds(): array
    {
        if (self::isSuperAdmin()) {
            return Brick::all()->pluck('id')->toArray();
        }

        $id = auth()?->id() ?? 0;

        return UserBricksView::getUserBrickIds($id);
    }
}
