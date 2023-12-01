<?php

use App\Filament\Pages\CoverageReport;
use App\Filament\Resources\VisitResource\Widgets\StatsOverview;
use App\Filament\Resources\VisitResource\Widgets\YearVisitsChart;
use App\Filament\Widgets\MonthlySalesChart;
return [

    /*
    |--------------------------------------------------------------------------
    | Broadcasting
    |--------------------------------------------------------------------------
    |
    | By uncommenting the Laravel Echo configuration, you may connect Filament
    | to any Pusher-compatible websockets server.
    |
    | This will allow your users to receive real-time notifications.
    |
    */

    'broadcasting' => [

        // 'echo' => [
        //     'broadcaster' => 'pusher',
        //     'key' => env('VITE_PUSHER_APP_KEY'),
        //     'cluster' => env('VITE_PUSHER_APP_CLUSTER'),
        //     'forceTLS' => true,
        // ],

    ],

    // /*
    // |--------------------------------------------------------------------------
    // | Filament Path
    // |--------------------------------------------------------------------------
    // |
    // | The default is `admin` but you can change it to whatever works best and
    // | doesn't conflict with the routing in your application.
    // |
    // */

    // 'path' => env('FILAMENT_PATH', '/'),

    // /*
    // |--------------------------------------------------------------------------
    // | Filament Core Path
    // |--------------------------------------------------------------------------
    // |
    // | This is the path which Filament will use to load its core routes and assets.
    // | You may change it if it conflicts with your other routes.
    // |
    // */

    // 'core_path' => env('FILAMENT_CORE_PATH', 'filament'),

    // /*
    // |--------------------------------------------------------------------------
    // | Filament Domain
    // |--------------------------------------------------------------------------
    // |
    // | You may change the domain where Filament should be active. If the domain
    // | is empty, all domains will be valid.
    // |
    // */

    // 'domain' => env('FILAMENT_DOMAIN'),

    // /*
    // |--------------------------------------------------------------------------
    // | Homepage URL
    // |--------------------------------------------------------------------------
    // |
    // | This is the URL that Filament will redirect the user to when they click
    // | on the sidebar's header.
    // |
    // */

    // 'home_url' => '/',

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | This is the namespace and directory that Filament will automatically
    | register dashboard widgets from. You may also register widgets here.
    |
    */

    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
        'path' => app_path('Filament/Widgets'),
        'register' => [
            StatsOverview::class,
            MonthlySalesChart::class,
            YearVisitsChart::class,
        ],
    ],

    'pages' => [
        'namespace' => 'App\\Filament\\Pages',
        'path' => app_path('Filament/Pages'),
        'register' => [
            CoverageReport::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | This is the storage disk Filament will use to put media. You may use any
    | of the disks defined in the `config/filesystems.php`.
    |
    */

    'default_filesystem_disk' => env('FILAMENT_FILESYSTEM_DISK', 'public'),

];
