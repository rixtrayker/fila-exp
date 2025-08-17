<?php

namespace App\Providers\Filament;

use App\Filament\Resources\VisitResource\Widgets\StatsOverview;
use App\Filament\Resources\VisitResource\Widgets\VisitCompletionChart;
use App\Filament\Resources\VisitResource\Widgets\WeeklyVisitsChart;
use App\Filament\Resources\VisitResource\Widgets\YearVisitsChart;
use App\Filament\Widgets\CoverageReportWidget;
use App\Filament\Widgets\DailyPlanSummaryWidget;
use App\Filament\Widgets\MonthlySalesChart;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->spa()
            ->default()
            ->id('admin')
            ->path('/admin')
            ->brandName('Avant Garde')
            // ->domain(config('app.url'))
            ->login()
            ->colors([
                'primary' => Color::Blue,
            ])
            ->resources([
                // Explicitly list only the resources we want to load
                // This prevents deprecated resources from being loaded
                \App\Filament\Resources\CoverageReportResource::class,
                \App\Filament\Resources\FrequencyReportResource::class,
                \App\Filament\Resources\VisitPerformanceReportResource::class,
                \App\Filament\Resources\ClientCoverageReportResource::class,
                // Add other active resources here as needed
            ])
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Visits')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Reports')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Requests')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Orders')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Admin management')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Companies management')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Zone management')
                    ->collapsible()
                    ->collapsed(),
                NavigationGroup::make()
                    ->label('Types management')
                    ->collapsible()
                    ->collapsed(),
            ])
            // ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                StatsOverview::class,
                DailyPlanSummaryWidget::class,
                VisitCompletionChart::class,
                CoverageReportWidget::class,
                // MonthlySalesChart::class,
                // WeeklyVisitsChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarFullyCollapsibleOnDesktop();
    }
}
