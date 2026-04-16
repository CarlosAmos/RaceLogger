<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\Season;
use App\Models\World;
use App\Models\TrackLayout;
use App\Models\CalendarRace;
use App\Models\SeasonClass;
use App\Models\PointSystem;
use Illuminate\Support\Facades\DB;
use App\Services\ChampionshipScenarioService;
use Inertia\Inertia;

class SeasonController extends Controller
{
    public function index()
    {
        $worldId = session('active_world_id');
        $world   = World::findOrFail($worldId);

        $seasons = Season::with(['series', 'calendarRaces', 'seasonClasses'])
            ->whereHas('series', fn ($q) => $q->where('world_id', $worldId))
            ->orderByDesc('year')
            ->get()
            ->groupBy('series_id')
            ->map(fn ($group) => [
                'series' => [
                    'id'   => $group->first()->series->id,
                    'name' => $group->first()->series->name,
                ],
                'seasons' => $group->map(fn ($s) => [
                    'id'           => $s->id,
                    'year'         => $s->year,
                    'round_count'  => $s->calendarRaces->count(),
                    'class_count'  => $s->seasonClasses->count(),
                ])->values(),
            ])
            ->values();

        return Inertia::render('seasons/index', [
            'world'   => ['id' => $world->id, 'name' => $world->name],
            'seasons' => $seasons,
        ]);
    }

    public function create(Request $request)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        $seriesId = $request->query('series_id');
        $series = Series::where('world_id', $worldId)->get();

        $defaultYear = $world->current_year;
        $seasonYear = $defaultYear;

        $layouts = TrackLayout::with('track')
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_from')
                      ->orWhere('active_from', '<=', $seasonYear);
            })
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_to')
                      ->orWhere('active_to', '>=', $seasonYear);
            })
            ->get();

        $pointSystems = PointSystem::with(['rules','bonusRules'])->get();

        return Inertia::render('seasons/create', [
            'season' => new Season(),
            'series' => $series,
            'seriesId' => $seriesId,
            'defaultYear' => $defaultYear,
            'layouts' => $layouts,
            'mode' => 'create',
            'worlds' => $world,
            'pointSystems' => $pointSystems,
            'tab' => 'circuits',
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer|min:1900|max:2100',

            'point_system_id' => 'nullable|exists:point_systems,id',

            'circuits' => 'required|array|min:1',
            'circuits.*.layout_id' => 'required|exists:track_layouts,id',
            'circuits.*.gp_name' => 'required|string|max:255',
            'circuits.*.race_code' => ['required','string','size:3','alpha'],
            'circuits.*.race_date' => 'required|date',
            'circuits.*.point_system_id' => 'nullable|exists:point_systems,id',
            'circuits.*.number_of_races' => 'nullable|integer|min:1|max:99',
            'circuits.*.endurance' => 'nullable|integer|min:0|max:1',

            'classes' => 'nullable|array',
            'classes.*' => 'required|string|max:255',
        ]);

        $raceCodes = collect($request->circuits)
            ->pluck('race_code')
            ->map(fn($code) => strtoupper($code));

        if ($raceCodes->count() !== $raceCodes->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'circuits' => 'Race codes must be unique within the season.',
            ]);
        }

        try {

            DB::beginTransaction();

            // Create Season (WITH default point system)
            $season = Season::create([
                'series_id' => $request->series_id,
                'year' => $request->year,
                'point_system_id' => $request->point_system_id ?: null,
            ]);

            // Save Classes
            if ($request->has('classes') && !empty($request->classes)) {
                foreach ($request->classes as $index => $className) {
                    SeasonClass::create([
                        'season_id' => $season->id,
                        'name' => $className,
                        'display_order' => $index + 1,
                    ]);
                }
            } else {
                SeasonClass::create([
                    'season_id' => $season->id,
                    'name' => 'Overall',
                    'display_order' => 1,
                ]);
            }

            // Save Calendar (WITH race override point system)
            foreach ($request->circuits as $index => $race) {

                CalendarRace::create([
                    'season_id' => $season->id,
                    'track_layout_id' => $race['layout_id'],
                    'round_number' => $index + 1,
                    'gp_name' => $race['gp_name'],
                    'race_code' => strtoupper($race['race_code']),
                    'race_date' => $race['race_date'],
                    'point_system_id' => $race['point_system_id'] ?? null,
                    'number_of_races' => $race['number_of_races'] ?? 1,
                    'endurance' => $race['endurance'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('dashboard')
                ->with('success', 'Season created successfully.');

        } catch (\Throwable $e) {

            DB::rollBack();
            dd($e->getMessage(), $e->getTraceAsString());
        }
    }

    public function show(Season $season)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        $tab = request('tab', 'calender');

        $season->load([
            'seasonClasses',
            'seasonEntries.entrant',
            'seasonEntries.entryClasses.raceClass',
            'seasonEntries.entryClasses.entryCars.carModel.engine',
            'seasonEntries.entryClasses.entryCars.carModel.constructor',
            'seasonEntries.entryClasses.entryCars.drivers.country',
            'calendarRaces.results.resultDrivers.driver',
            'calendarRaces.raceSessions',
            'calendarRaces.qualifyingSessions.results',
        ]);
        
        $scenarioService = new ChampionshipScenarioService();

        $classScenarios = [];

        foreach ($season->seasonClasses as $class) {

            $scenario = $scenarioService->getClinchTable(
                $season->id,
                $class->id
            );

            if ($scenario) {
                $classScenarios[$class->id] = $scenario;
            }
        }

        $series = Series::where('id', $season->series_id)->get();

        return Inertia::render('seasons/show', compact(
            'world',
            'season',
            'series',
            'tab',
            'classScenarios'
        ));
    }

    public function edit(World $world, Season $season)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        $tab = request('tab', 'teams');

        abort_unless($season->series->world_id == $worldId, 403);

        $series = Series::where('world_id', $worldId)->get();

        $seasonYear = $season->year;

        $layouts = TrackLayout::with(['track.country'])
            ->activeForYear($seasonYear)
            ->get();

        $calendarRaces = $season->calendarRaces()
            ->with(['layout.track.country'])
            ->orderBy('round_number')
            ->get();

        $pointSystems = PointSystem::with(['rules','bonusRules'])->get();


        $season->load([
            'seasonClasses',
            'seasonEntries.entrant',
            'seasonEntries.entryClasses.raceClass',
            'seasonEntries.entryClasses.entryCars.carModel.engine',
            'seasonEntries.entryClasses.entryCars.carModel.constructor',
            'seasonEntries.entryClasses.entryCars.drivers.country',
            'calendarRaces.results',
            'calendarRaces.layout.track.country'
        ]);

        return Inertia::render('seasons/edit', [
            'season' => $season,
            'series' => $series,
            'seriesId' => $season->series_id,
            'defaultYear' => $season->year,
            'layouts' => $layouts,
            'calendarRaces' => $calendarRaces,
            'mode' => 'edit',
            'worlds' => $world,
            'pointSystems' => $pointSystems,
            'tab' => $tab,
        ]);
    }

    public function update(Request $request, Season $season)
    {

        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer|min:1900|max:2100',

            'point_system_id' => 'nullable|exists:point_systems,id',

            'circuits' => 'required|array|min:1',
            'circuits.*.layout_id' => 'required|exists:track_layouts,id',
            'circuits.*.gp_name' => 'required|string|max:255',
            'circuits.*.race_code' => ['required','string','size:3','alpha'],
            'circuits.*.race_date' => 'required|date',
            'circuits.*.sprint_race' => 'required|integer|min:0|max:1',
            'circuits.*.point_system_id' => 'nullable|exists:point_systems,id',
            'circuits.*.number_of_races' => 'nullable|integer|min:1|max:99',
            'circuits.*.endurance' => 'nullable|integer|min:0|max:1',

            'classes' => 'nullable|array',
            'classes.*' => 'required|string|max:255',
        ]);

        $raceCodes = collect($request->circuits)
            ->pluck('race_code')
            ->map(fn($code) => strtoupper($code));

        if ($raceCodes->count() !== $raceCodes->unique()->count()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'circuits' => 'Race codes must be unique within the season.',
            ]);
        }

        try {

            DB::beginTransaction();

            $hasResults = $season->calendarRaces()
                ->whereHas('results')
                ->exists();

            if ($hasResults) {
                DB::rollBack();
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'season' => 'Calendar cannot be modified because results already exist.',
                ]);
            }

            // Update season (WITH default point system)
            $season->update([
                'series_id' => $request->series_id,
                'year' => $request->year,
                'point_system_id' => $request->point_system_id ?: null,
            ]);

            // Sync classes
            $existingClassIds = [];

            if ($request->has('classes') && !empty($request->classes)) {

                foreach ($request->classes as $index => $className) {


                    $seasonClass = $season->classes()
                        ->where('name', $className)
                        ->first();

                    if ($seasonClass) {
                        $seasonClass->update([
                            'display_order' => $index + 1,
                        ]);
                    } else {
                        $seasonClass = $season->classes()->create([
                            'name' => $className,
                            'display_order' => $index + 1,
                        ]);
                    }

                    $existingClassIds[] = $seasonClass->id;
                }
            } else {

                $seasonClass = $season->classes()->firstOrCreate(
                    ['name' => 'Overall'],
                    ['display_order' => 1]
                );

                $existingClassIds[] = $seasonClass->id;
            }

            $season->classes()
                ->whereNotIn('id', $existingClassIds)
                ->whereDoesntHave('entryClasses')
                ->delete();

            // Delete old calendar
            $season->calendarRaces()->delete();

            // Recreate calendar (WITH race override point system)
            foreach ($request->circuits as $index => $race) {

                CalendarRace::firstOrCreate([
                    'season_id' => $season->id,
                    'track_layout_id' => $race['layout_id'],
                    'round_number' => $index + 1,
                    'gp_name' => $race['gp_name'],
                    'race_code' => strtoupper($race['race_code']),
                    'race_date' => $race['race_date'],
                    'sprint_race' => $race['sprint_race'],
                    'point_system_id' => $race['point_system_id'] ?? null,
                    'number_of_races' => $race['number_of_races'] ?? 1,
                    'endurance' => $race['endurance'] ?? 0,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('seasons.show', [$season])
                ->with('success', 'Season updated successfully.');

        } catch (\Throwable $e) {

            DB::rollBack();
            dd($e->getMessage(), $e->getTraceAsString());
        }
    }

    public function destroy(Season $season)
    {
        $season->delete();

        return redirect()->route('seasons.index')
            ->with('success', 'Season deleted.');
    }
}