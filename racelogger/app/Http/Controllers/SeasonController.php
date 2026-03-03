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

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::with('series.world')->paginate(15);
        return view('seasons.index', compact('seasons'));
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

        return view('seasons.form', [
            'season' => new Season(),
            'series' => $series,
            'seriesId' => $seriesId,
            'defaultYear' => $defaultYear,
            'layouts' => $layouts,
            'mode' => 'create',
            'worlds' => $world,
            'pointSystems' => $pointSystems
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

            'classes' => 'nullable|array',
            'classes.*' => 'required|string|max:255',
        ]);

        $raceCodes = collect($request->circuits)
            ->pluck('race_code')
            ->map(fn($code) => strtoupper($code));

        if ($raceCodes->count() !== $raceCodes->unique()->count()) {
            return back()
                ->withErrors(['circuits' => 'Race codes must be unique within the season.'])
                ->withInput();
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
            'calendarRaces.results',
        ]);

        return view('seasons.show', compact(
            'world',
            'season',
            'tab'
        ));
    }

    public function edit(World $world, Season $season)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

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
        ]);

        return view('seasons.form', [
            'season' => $season,
            'series' => $series,
            'seriesId' => $season->series_id,
            'defaultYear' => $season->year,
            'layouts' => $layouts,
            'calendarRaces' => $calendarRaces,
            'mode' => 'edit',
            'worlds' => $world,
            'pointSystems' => $pointSystems
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
            'circuits.*.point_system_id' => 'nullable|exists:point_systems,id',

            'classes' => 'nullable|array',
            'classes.*' => 'required|string|max:255',
        ]); 
        
        

        $raceCodes = collect($request->circuits)
            ->pluck('race_code')
            ->map(fn($code) => strtoupper($code));

        if ($raceCodes->count() !== $raceCodes->unique()->count()) {
            return back()
                ->withErrors(['circuits' => 'Race codes must be unique within the season.'])
                ->withInput();
        }

        try {

            DB::beginTransaction();

            $hasResults = $season->calendarRaces()
                ->whereHas('results')
                ->exists();

            if ($hasResults) {
                DB::rollBack();
                return back()
                    ->withErrors(['season' => 'Calendar cannot be modified because results already exist.'])
                    ->withInput();
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

                CalendarRace::create([
                    'season_id' => $season->id,
                    'track_layout_id' => $race['layout_id'],
                    'round_number' => $index + 1,
                    'gp_name' => $race['gp_name'],
                    'race_code' => strtoupper($race['race_code']),
                    'race_date' => $race['race_date'],
                    'point_system_id' => $race['point_system_id'] ?? null,
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