<?php

namespace App\Providers;

use App\Filament\Resources\PermissionResource;
use App\Filament\Resources\RoleResource;
use App\Filament\Resources\UserResource;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use Filament\Navigation\UserMenuItem;
use Illuminate\Foundation\Vite;
use Illuminate\Support\ServiceProvider;
use Yepsua\Filament\Themes\Filament\Forms\Components\ThemesSelect;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Filament::navigation(function (NavigationBuilder $builder): NavigationBuilder {
        //     return $builder->items([
        //         NavigationItem::make('Dashboard')
        //             ->icon('heroicon-o-home')
        //             ->activeIcon('heroicon-s-home')
        //             ->isActiveWhen(fn (): bool => request()->routeIs('filament.pages.dashboard'))
        //             ->url(route('filament.pages.dashboard')),
        //         ...UserResource::getNavigationItems(),
        //     ]);
        // });

        Filament::serving(function() {

            // Filament::registerTheme(
            //     app(Vite::class)('resources/css/app.css'),
            // );

            if(auth()->user() && auth()->user()->is_admin === 1 && auth()->user()->hasAnyRole(['super-admin','admin','moderator'])){
                Filament::registerUserMenuItems([
                    UserMenuItem::make()
                        ->label('Manage Users')
                        ->url(UserResource::getUrl())
                        ->icon('heroicon-s-users'),

                    UserMenuItem::make()
                    ->label('Manage Roles')
                    ->url(RoleResource::getUrl())
                    ->icon('heroicon-s-cog'),

                    UserMenuItem::make()
                    ->label('Manage Permissions')
                    ->url(PermissionResource::getUrl())
                    ->icon('heroicon-s-key'),
                ]);
            }
        });
    }
}
