<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use App\Http\Controllers\WorldController;
use App\Http\Controllers\SeriesController;
use App\Http\Controllers\SeasonController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\RaceSessionController;
use App\Http\Controllers\ResultController;
use App\Http\Controllers\PointsSystemController;
use App\Http\Controllers\CalendarRaceController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\TrackLayoutController;
use App\Http\Controllers\ConstructorController;

Route::get('/', function () {
    return Inertia::render('welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';


Route::resource('worlds', WorldController::class);
Route::resource('series', SeriesController::class);
Route::resource('seasons', SeasonController::class);
Route::resource('teams', TeamController::class);
Route::resource('drivers', DriverController::class);
Route::resource('calendar-races', CalendarRaceController::class);
Route::resource('race-sessions', RaceSessionController::class);
Route::resource('results', ResultController::class);
Route::resource('points-systems', PointsSystemController::class);


Route::get('/', [WorldController::class, 'index'])
    ->name('world.select');
Route::post('/world/select/{world}', [WorldController::class, 'select'])
    ->name('world.select.store');
Route::middleware(['world.selected'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});
Route::get('/worlds/{world}/created', [WorldController::class, 'created'])
    ->name('worlds.created');


Route::get('/series/{series}/created', [SeriesController::class, 'created'])
    ->name('series.created');

Route::resource('tracks', TrackController::class);
Route::resource('track-layouts', TrackLayoutController::class);
//Route::resource('constructors', ConstructorController::class)->scoped();
Route::resource('worlds.constructors', ConstructorController::class);