<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use App\Jobs\OptimizeAppPerformance;
use App\Models\Area;
use App\Models\Brick;
use Illuminate\Support\Facades\DB;
use Laravel\Octane\Swoole\SwooleExtension;

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

Route::get('/test', function () {
    return \App\Models\User::find(1)->editRequests;
});

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

// Route::get('/login', [\Filament\Http\Livewire\Auth::class,'login'])->name('filament.auth.login');
// Route::get('/logout', [\Filament\Http\Livewire\Auth::class,'logout'])->name('filament.app.auth.logout');

// Route::middleware([
//     'auth:sanctum',
//     config('jetstream.auth_session'),
//     'verified'
// ])->group(function () {
//     Route::get('/dashboard', function () {
//         return view('dashboard');
//     })->name('dashboard');
// });
