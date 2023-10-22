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
        $table = $model->getTable();
        if(auth()->user() && auth()->user()->hasRole('medical-rep')){
            $builder->where('user_id', '=', auth()->id());
            return;
        }

        if(auth()->user() && auth()->user()->hasRole(['country-manager','area-manager','district-manager'])) {
            $ids = User::descendantsAndSelf(auth()->user())->pluck( $table.'.id')->toArray();
            $builder->whereIn('user_id', $ids);
        }
        return $builder;
    }
}
