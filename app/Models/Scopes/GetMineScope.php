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
        if(auth()->user() && auth()->user()->hasRole('medical-rep')){
            $builder->where('user_id', '=', auth()->id());
        }

        if(auth()->user() && auth()->user()->hasRole(['country-manager','area-manager','district-manager'])) {

            $query = DB::table('users')
                ->where('id',auth()->id())
                ->unionAll(
                    DB::table('users')
                        ->select('users.*')
                        ->join('tree', 'tree.id', '=', 'users.manager_id')
                );

            $tree = DB::table('users')
                ->withRecursiveExpression('tree', $query)
                ->pluck('id')->toArray();

            $tree[] = auth()->id();
            $builder->whereIn('user_id', $tree);
        }

    }
}
