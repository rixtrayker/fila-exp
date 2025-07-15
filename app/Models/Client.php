<?php

namespace App\Models;

use App\Traits\HasEditRequest;
use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Client extends Model
{
    use HasFactory;
    use HasEditRequest;
    use HasRelationships;

    protected $appends = ["name", "mapUrl"];

    protected $with = ["brick.area"];
    protected $fillable = [
        "name_en",
        "name_ar",
        "email",
        "phone",
        "address",
        "location",
        "brick_id",
        "grade",
        "shift",
        "related_pharmacy",
        "am_work",
        "client_type_id",
        "speciality_id",
        "lat",
        "lng",
    ];
    public $editable = [
        "name_en",
        "name_ar",
        "email",
        "phone",
        "address",
        // 'location',
        "brick_id",
        "grade",
        "shift",
        "related_pharmacy",
        "am_work",
        "client_type_id",
        "speciality_id",
        "lat",
        "lng",
    ];

    public function setLocationAttribute($value)
    {
        $this->attributes["location"] = json_encode($value);
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
        return $this->name_en . " - " . $this->name_ar;
    }

    public function mapUrl(): string|null
    {
        if (!$this->lat || !$this->lng) {
            return null;
        }
        return "https://www.google.com/maps/place/" .
            $this->lat .
            "," .
            $this->lng;
    }

    public function getMapUrlAttribute(): string|null
    {
        return $this->mapUrl();
    }

    public function setLocation($value)
    {
        $this->lat = $value["lat"];
        $this->lng = $value["lng"];
        $this->location = $value;
        $this->save();
    }

    public function scopeFilter($query, array $filters)
    {
        $query->when($filters["search"] ?? null, function ($query, $search) {
            $query->whereJsonContains("name", $search);
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
        return $query->where("client_type_id", ClientType::PH);
    }

    public function scopeInMyAreas($builder)
    {
        if (!self::isAuthenticated()) {
            return;
        }

        if (self::isSuperAdmin()) {
            return $builder;
        }

        $brickIds = self::getUserBrickIds();

        return $builder->whereIn("brick_id", $brickIds);
    }

    private static function isAuthenticated(): bool
    {
        return auth()->check();
    }

    private static function isSuperAdmin(): bool
    {
        return auth()->user()->hasRole("super-admin");
    }

    private static function getUserBrickIds(): array
    {
        $user = auth()->user();

        // Start with an empty collection
        $brickIds = collect();

        // Get brick IDs from user's areas using a single query
        if ($user->areas()->exists()) {
            $areaBrickIds = $user
                ->areas()
                ->with("bricks")
                ->get()
                ->map(function ($area) {
                    return $area->bricks->pluck("id");
                })
                ->flatten();

            $brickIds = $brickIds->merge($areaBrickIds);
        }

        // If user is medical rep, add their direct brick assignments
        if ($user->hasRole("medical-rep")) {
            $userBrickIds = $user->bricks()->pluck("bricks.id");
            $brickIds = $brickIds->merge($userBrickIds);
        }

        return $brickIds->unique()->values()->toArray();
    }
}
