<?php

use App\Http\Controllers\SystemUtilityController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Jobs\OptimizeAppPerformance;
use App\Models\Area;
use App\Models\Brick;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Laravel\Octane\Swoole\SwooleExtension;
use App\Jobs\FixOrdersWith0Total;
use Symfony\Component\Process\Process;
use App\Http\Controllers\TemplateFileController;
use App\Http\Controllers\ClientRequestAttachmentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Route::get('/test', function () {
//     Client::with('visits')->get();
//     return 1;
// });

Route::get('/admin/ops/start-swoole', function () {
    (new SwooleExtension)->isInstalled();
    if(extension_loaded('swoole'))
        return 1;
    return 0;
});

Route::get('/admin/ops/optimize-app', function () {
    dispatch(new OptimizeAppPerformance());
    return true;
});

// Route::get('/admin/migrate-areas', function () {
//    $areas = Area::with('bricks')->get();
//    $data = [];

//     foreach($areas as $area){
//         $bricks = $area->bricks;

//         foreach($bricks as $brick){
//             $data[] = [
//                 'id' =>  $brick->id,
//                 'area_id' => $area->id,
//             ];
//         }
//     }
//     Brick::upsert($data, ['id'],['area_id']);
//     return true;
// });

Route::get('/admin/ops/migrate-plan-data', function () {
    Artisan::call('db:seed', [
        '--class' => 'MigratePlanData',
    ]);
    return true;
});
// artisan migrate
Route::get('/admin/ops/migrate', function () {
    Artisan::call('migrate');
    return Artisan::output();
});

// composer dump-autoload
Route::get('/admin/ops/run-composer-dump-autoload', [SystemUtilityController::class, 'dumpAutoload']);
    // ->middleware(['auth', 'can:run-system-commands']);

// php version
Route::get('/admin/ops/php-version', function () {
    return phpversion();
});

// seed all roles and permissions
Route::get('/admin/ops/seed-roles-and-permissions', function () {
    DB::statement('SET FOREIGN_KEY_CHECKS = 0');
    DB::table('permissions')->truncate();
    DB::statement('SET FOREIGN_KEY_CHECKS = 1');

    $seeders = [
        'RolesAndPermissionsSeeder',
        'MedicalRepRole',
        'DistrictManagerRole',
        'AreaManagerRole',
        'CountryManagerRole',
    ];

    foreach ($seeders as $seeder) {
        Artisan::call('db:seed', ['--class' => $seeder]);
    }
    return true;
});

Route::get('/admin/ops/fix-orders-with-0-total', function () {
    dispatch(new FixOrdersWith0Total());
    return true;
});

Route::get('/admin/ops/clear-permission-cache', function () {
    Artisan::call('permission:cache-reset');
    return true;
});

// Route::get('/login', [\Filament\Http\Livewire\Auth::class,'login'])->name('filament.auth.login');
// Route::get('/logout', [\Filament\Http\Livewire\Auth::class,'logout'])->name('filament.app.auth.logout');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});


// Template file download route
Route::get('/template-files/{templateFile}/download', [TemplateFileController::class, 'download'])
    ->name('template-files.download')
    ->middleware('auth');

// Client request attachment routes
Route::get('/client-requests/{clientRequest}/attachments/{filename}/download', [ClientRequestAttachmentController::class, 'download'])
    ->name('client-requests.attachments.download')
    ->middleware('auth');

Route::get('/client-requests/{clientRequest}/attachments/{filename}/stream', [ClientRequestAttachmentController::class, 'stream'])
    ->name('client-requests.attachments.stream')
    ->middleware('auth');

// Client request zip download route
Route::get('/client-requests/{clientRequest}/zip/download', [ClientRequestAttachmentController::class, 'downloadZip'])
    ->name('client-requests.zip.download')
    ->middleware('auth');

// link the storage
Route::get('/admin/ops/link-storage', function () {
    Artisan::call('storage:link');
    return Artisan::output();
});
