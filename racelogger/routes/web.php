<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
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
use App\Http\Controllers\EntrantController;
use App\Http\Controllers\SeasonEntryController;
use App\Http\Controllers\EntryClassController;
use App\Http\Controllers\ConstructorCarModelController;
use App\Http\Controllers\WorldEngineController;
use App\Http\Controllers\EntryCarController;
use App\Http\Controllers\WorldDriverController;
use App\Http\Controllers\EntryCarDriverController;
use App\Http\Controllers\PointSystemController;
use App\Http\Controllers\RaceWeekendController;
use App\Http\Controllers\LapRecordController;
use App\Http\Controllers\ImportController;


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
    Route::get('/records', [LapRecordController::class, 'index'])
        ->name('records.index');
});
Route::get('/worlds/{world}/created', [WorldController::class, 'created'])
    ->name('worlds.created');


Route::get('/series/{series}/created', [SeriesController::class, 'created'])
    ->name('series.created');

Route::resource('tracks', TrackController::class);
Route::resource('track-layouts', TrackLayoutController::class);
//Route::resource('constructors', ConstructorController::class)->scoped();
Route::resource('worlds.constructors', ConstructorController::class);
Route::resource('worlds.entrants', EntrantController::class);
Route::resource('worlds.seasons', SeasonController::class);
Route::post('/seasons/{season}/acc/assign-car', [SeasonController::class, 'accAssignCar'])
    ->name('seasons.acc.assign-car');
Route::post('/seasons/{season}/acc/create-entry', [SeasonController::class, 'accCreateEntry'])
    ->name('seasons.acc.create-entry');
Route::post('/seasons/{season}/acc/assign-drivers', [SeasonController::class, 'accAssignDrivers'])
    ->name('seasons.acc.assign-drivers');

Route::resource(
    'worlds.seasons.season-entries',
    SeasonEntryController::class
);

Route::resource(
    'worlds.constructors.car-models',
    ConstructorCarModelController::class
);

Route::resource('worlds.engines', WorldEngineController::class);


Route::resource(
    'worlds.seasons.season-entries.entry-classes',
    EntryClassController::class
);

Route::resource(
    'worlds.seasons.season-entries.entry-classes.entry-cars',
    EntryCarController::class
);

Route::prefix('worlds/{world}/seasons/{season}/season-entries/{seasonEntry}')
->group(function () {

    // static routes first
    Route::get('entry_create', [EntryCarController::class, 'create_entry'])
        ->name('entry-cars.create_entry');

    Route::post('entry_create', [EntryCarController::class, 'store_entry'])
        ->name('entry-cars.store');

});

Route::resource('worlds.drivers', WorldDriverController::class);

Route::prefix('worlds/{world}/seasons/{season}/season-entries/{seasonEntry}/entry-classes/{entryClass}/entry-cars/{entryCar}')
    ->group(function () {

        Route::get('drivers', [EntryCarDriverController::class, 'edit'])
            ->name('entry-cars.drivers.edit');

        Route::post('drivers', [EntryCarDriverController::class, 'update'])
            ->name('entry-cars.drivers.update');
    });

Route::resource('point-systems', PointSystemController::class);

Route::get('/races/{race}', [RaceWeekendController::class, 'show'])
    ->name('races.show');

Route::post(
    '/races/{race}/weekend',
    [RaceWeekendController::class, 'update']
)->name('races.weekend.update');

Route::get('/import', [ImportController::class, 'index'])->name('import.index');
Route::post('/import/run', [ImportController::class, 'run'])->name('import.run');