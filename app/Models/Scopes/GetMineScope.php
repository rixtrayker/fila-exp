<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\DB;
use Staudenmeir\LaravelCte\Query\Builder as Cte;

class GetMineScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $builder->whereIn('user_id', self::getUserIds());
    }

    public static function getUserIds(): array
    {
        if (!auth()->user()) {
            return [];
        }

        // @phpstan-ignore-next-line - hasRole method exists from Spatie Permission package
        if(auth()->user()->hasRole('medical-rep')){
            return [auth()->id()];
        }

        $userIds = User::descendantsAndSelf(auth()->user())->pluck('id')->toArray();
        return $userIds;
    }
}
