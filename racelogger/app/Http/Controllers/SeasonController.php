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
use App\Models\Driver;
use App\Models\Entrant;
use App\Models\Constructor;
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
            'circuits.*.special_event' => 'nullable|integer|min:0|max:1',

            'classes' => 'nullable|array',
            'classes.*.name' => 'required|string|max:255',
            'classes.*.sub_class' => 'nullable|string|max:255',
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
                foreach ($request->classes as $index => $classData) {
                    SeasonClass::create([
                        'season_id'     => $season->id,
                        'name'          => $classData['name'],
                        'sub_class'     => $classData['sub_class'] ?? null,
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
                    'special_event' => $race['special_event'] ?? 0,
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
            'pointSystem.rules',
            'pointSystem.bonusRules',
            'calendarRaces.pointSystem.rules',
            'calendarRaces.pointSystem.bonusRules',
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

        $accImport = null;
        if ($series->first()?->game === 'acc') {
            $accImport = $this->resolveAccImport($season);
        }

        $allEntrants = Entrant::where('world_id', $worldId)
            ->orderBy('name')
            ->get(['id', 'name']);

        $constructors = Constructor::where('world_id', $worldId)
            ->orderBy('name')
            ->get(['id', 'name']);

        return Inertia::render('seasons/show', compact(
            'world',
            'season',
            'series',
            'tab',
            'classScenarios',
            'accImport',
            'allEntrants',
            'constructors'
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

        $drivers = Driver::where('world_id', $worldId)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

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
            'drivers' => $drivers,
        ]);
    }

    public function update(Request $request, Season $season)
    {

        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer|min:1900|max:2100',
            'game' => 'nullable|string|in:acc,lmu,ac_evo',

            'point_system_id'    => 'nullable|exists:point_systems,id',
            'replace_driver_id'  => 'nullable|exists:drivers,id',
            'substitute_driver_id' => 'nullable|exists:drivers,id',

            'circuits' => 'required|array|min:1',
            'circuits.*.id' => 'nullable|integer|exists:calendar_races,id',
            'circuits.*.layout_id' => 'required|exists:track_layouts,id',
            'circuits.*.gp_name' => 'required|string|max:255',
            'circuits.*.race_code' => ['required','string','size:3','alpha'],
            'circuits.*.race_date' => 'required|date',
            'circuits.*.sprint_race' => 'required|integer|min:0|max:1',
            'circuits.*.point_system_id' => 'nullable|exists:point_systems,id',
            'circuits.*.number_of_races' => 'nullable|integer|min:1|max:99',
            'circuits.*.endurance' => 'nullable|integer|min:0|max:1',
            'circuits.*.special_event' => 'nullable|integer|min:0|max:1',

            'classes' => 'nullable|array',
            'classes.*.name' => 'required|string|max:255',
            'classes.*.sub_class' => 'nullable|string|max:255',
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
                'series_id'           => $request->series_id,
                'year'                => $request->year,
                'point_system_id'     => $request->point_system_id ?: null,
                'replace_driver_id'   => $request->replace_driver_id ?: null,
                'substitute_driver_id' => $request->substitute_driver_id ?: null,
            ]);

            // Update series game
            Series::where('id', $request->series_id)
                ->update(['game' => $request->game ?: null]);

            // Sync classes
            $existingClassIds = [];

            if ($request->has('classes') && !empty($request->classes)) {

                foreach ($request->classes as $index => $classData) {
                    $seasonClass = $season->classes()
                        ->where('name', $classData['name'])
                        ->where('sub_class', $classData['sub_class'] ?? null)
                        ->first();

                    if ($seasonClass) {
                        $seasonClass->update(['display_order' => $index + 1]);
                    } else {
                        $seasonClass = $season->classes()->create([
                            'name'          => $classData['name'],
                            'sub_class'     => $classData['sub_class'] ?? null,
                            'display_order' => $index + 1,
                        ]);
                    }

                    $existingClassIds[] = $seasonClass->id;
                }
            } else {

                $seasonClass = $season->classes()->firstOrCreate(
                    ['name' => 'Overall', 'sub_class' => null],
                    ['display_order' => 1]
                );

                $existingClassIds[] = $seasonClass->id;
            }

            $season->classes()
                ->whereNotIn('id', $existingClassIds)
                ->whereDoesntHave('entryClasses')
                ->delete();

            // Update calendar in-place to preserve IDs (important for file references)
            $submittedIds = [];

            foreach ($request->circuits as $index => $race) {
                $attrs = [
                    'season_id'        => $season->id,
                    'track_layout_id'  => $race['layout_id'],
                    'round_number'     => $index + 1,
                    'gp_name'          => $race['gp_name'],
                    'race_code'        => strtoupper($race['race_code']),
                    'race_date'        => $race['race_date'],
                    'sprint_race'      => $race['sprint_race'],
                    'point_system_id'  => $race['point_system_id'] ?? null,
                    'number_of_races'  => $race['number_of_races'] ?? 1,
                    'endurance'        => $race['endurance'] ?? 0,
                    'special_event'    => $race['special_event'] ?? 0,
                ];

                if (!empty($race['id'])) {
                    $calRace = CalendarRace::find($race['id']);
                    if ($calRace && $calRace->season_id === $season->id) {
                        $calRace->update($attrs);
                        $submittedIds[] = $calRace->id;
                        continue;
                    }
                }

                $created = CalendarRace::create($attrs);
                $submittedIds[] = $created->id;
            }

            // Remove calendar races that were deleted in the editor
            $season->calendarRaces()
                ->whereNotIn('id', $submittedIds)
                ->delete();

            DB::commit();

            return redirect()
                ->route('seasons.show', [$season])
                ->with('success', 'Season updated successfully.');

        } catch (\Throwable $e) {

            DB::rollBack();
            dd($e->getMessage(), $e->getTraceAsString());
        }
    }

    /**
     * Find the first unlocked ACC race in this season, auto-insert new drivers,
     * and return unmatched teams/cars for the import modal.
     */
    private function resolveAccImport(Season $season): array
    {
        $worldId = $season->series->world_id;

        $race = CalendarRace::where('season_id', $season->id)
            ->where('is_locked', 0)
            ->orderBy('id', 'asc')
            ->first();

        if (!$race) {
            return ['race' => null, 'teams' => []];
        }

        $folder = public_path("acc_races/{$race->id}");

        if (!is_dir($folder)) {
            return ['race' => null, 'teams' => []];
        }

        $qualFile = null;
        foreach (['/Qualifying_1.json', '/Qualifying.json'] as $candidate) {
            if (file_exists($folder . $candidate)) {
                $qualFile = $folder . $candidate;
                break;
            }
        }

        if (!$qualFile) {
            return ['race' => null, 'teams' => []];
        }

        $raw = file_get_contents($qualFile);

        if (substr($raw, 0, 2) === "\xFF\xFE") {
            $raw = substr($raw, 2);
        }
        $raw = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');

        $data = json_decode($raw, true);

        if (!isset($data['snapShot']['leaderBoardLines'])) {
            return ['race' => null, 'teams' => []];
        }

        $seasonId   = $race->season_id;
        $teamGroups = [];

        foreach ($data['snapShot']['leaderBoardLines'] as $line) {
            $car      = $line['car'];
            $teamGuid = $car['teamGuid'] ?? $car['teamName'];
            $teamName = $car['teamName'];
            $carNo    = (string) $car['raceNumber'];

            // Auto-insert drivers, copying country_id from another world if available
            $drivers = [];
            foreach ($car['drivers'] as $d) {
                $exists = Driver::where('world_id', $worldId)
                    ->where('first_name', $d['firstName'])
                    ->where('last_name', $d['lastName'])
                    ->exists();

                if (!$exists) {
                    $template = Driver::where('first_name', $d['firstName'])
                        ->where('last_name', $d['lastName'])
                        ->whereNotNull('country_id')
                        ->first();

                    Driver::create([
                        'world_id'   => $worldId,
                        'first_name' => $d['firstName'],
                        'last_name'  => $d['lastName'],
                        'country_id' => $template?->country_id,
                    ]);
                }

                $drivers[] = ['first_name' => $d['firstName'], 'last_name' => $d['lastName']];
            }

            if (!isset($teamGroups[$teamGuid])) {
                $teamGroups[$teamGuid] = ['team_names' => [], 'cars' => [], 'nationality' => $car['nationality'] ?? null];
            }

            if (!in_array($teamName, $teamGroups[$teamGuid]['team_names'])) {
                $teamGroups[$teamGuid]['team_names'][] = $teamName;
            }

            $teamGroups[$teamGuid]['cars'][] = [
                'car_number'  => $carNo,
                'livery_name' => $teamName,
                'drivers'     => $drivers,
            ];
        }

        // Resolve common name and match status per team group
        $teams = [];

        foreach ($teamGroups as $teamGuid => $group) {
            $commonName  = null;
            $teamMatched = false;

            foreach ($group['team_names'] as $name) {
                $matched = \App\Models\SeasonEntry::where('season_id', $seasonId)
                        ->whereHas('entrant', fn($q) => $q->where('name', $name)->where('world_id', $worldId))
                        ->exists()
                    || \App\Models\EntryCar::where('livery_name', $name)
                        ->whereHas('entryClass.seasonEntry', fn($q) => $q->where('season_id', $seasonId))
                        ->exists();

                if ($matched) {
                    $commonName  = $name;
                    $teamMatched = true;
                    break;
                }
            }

            if ($commonName === null) {
                $commonName = $group['team_names'][0];
            }

            $cars = [];
            foreach ($group['cars'] as $carData) {
                $carMatched = \App\Models\EntryCar::where('car_number', $carData['car_number'])
                    ->whereHas('entryClass.seasonEntry', fn($q) => $q->where('season_id', $seasonId))
                    ->exists();

                $cars[] = [
                    'car_number'     => $carData['car_number'],
                    'livery_name'    => $carData['livery_name'],
                    'livery_differs' => $carData['livery_name'] !== $commonName,
                    'car_matched'    => $carMatched,
                    'drivers'        => $carData['drivers'],
                ];
            }

            $hasUnmatched = !$teamMatched || collect($cars)->contains(fn($c) => !$c['car_matched']);

            if ($hasUnmatched) {
                $teams[] = [
                    'team_guid'    => $teamGuid,
                    'team_name'    => $commonName,
                    'team_matched' => $teamMatched,
                    'nationality'  => $group['nationality'],
                    'cars'         => $cars,
                ];
            }
        }

        // Entrants enrolled in this season, enriched with constructor + first entry class
        $entrants = \App\Models\SeasonEntry::where('season_id', $seasonId)
            ->with(['entrant', 'entryClasses'])
            ->get()
            ->filter(fn($se) => $se->entrant !== null)
            ->map(fn($se) => [
                'id'             => $se->id,
                'name'           => $se->entrant->name,
                'display_name'   => $se->display_name ?? $se->entrant->name,
                'constructor_id' => $se->constructor_id,
                'entry_class_id' => $se->entryClasses->first()?->id,
            ])
            ->sortBy('name')
            ->values()
            ->toArray();

        // Car models for this world, keyed by constructor for frontend filtering
        $carModels = \App\Models\CarModel::whereHas('constructor', fn($q) => $q->where('world_id', $worldId))
            ->orderBy('name')
            ->get(['id', 'name', 'constructor_id'])
            ->toArray();

        return [
            'race' => [
                'id'        => $race->id,
                'gp_name'   => $race->gp_name,
                'race_code' => $race->race_code,
                'round'     => $race->round_number,
            ],
            'teams'      => $teams,
            'entrants'   => $entrants,
            'car_models' => $carModels,
        ];
    }

    /**
     * Create an entry car from an ACC import assignment.
     * Livery name is set when the ACC team name differs from the season entry's display name.
     * Drivers from the ACC file are attached; the season substitution rule is applied if a
     * driver's name matches replace_driver_id, swapping in substitute_driver_id instead.
     */
    public function accAssignCar(Request $request, Season $season)
    {
        $request->validate([
            'season_entry_id' => 'required|exists:season_entries,id',
            'season_class_id' => 'required|exists:season_classes,id',
            'car_number'      => 'required|string|max:10',
            'car_model_id'    => 'required|exists:car_models,id',
            'livery_name'     => 'nullable|string|max:255',
            'drivers'         => 'nullable|array',
            'drivers.*.first_name' => 'required|string',
            'drivers.*.last_name'  => 'required|string',
        ]);

        $worldId     = $season->series->world_id;
        $seasonEntry = \App\Models\SeasonEntry::findOrFail($request->season_entry_id);

        $entryClass = $seasonEntry->entryClasses()->firstOrCreate([
            'race_class_id' => $request->season_class_id,
        ]);

        $entryCar = $entryClass->entryCars()->create([
            'car_number'   => $request->car_number,
            'car_model_id' => $request->car_model_id,
            'livery_name'  => $request->livery_name,
        ]);

        // Resolve and attach drivers, applying substitution rule
        $season->load(['replaceDriver', 'substituteDriver']);

        foreach ($request->drivers ?? [] as $d) {
            $driver = Driver::where('world_id', $worldId)
                ->where('first_name', $d['first_name'])
                ->where('last_name', $d['last_name'])
                ->first();

            if (!$driver) {
                continue;
            }

            // Apply substitution: swap out replace_driver with substitute_driver
            if ($season->replaceDriver && $season->substituteDriver
                && $driver->id === $season->replace_driver_id) {
                $driver = $season->substituteDriver;
            }

            $entryCar->drivers()->syncWithoutDetaching([$driver->id]);
        }

        return back();
    }

    /**
     * Bulk-assign drivers to all existing entry cars in the season using the ACC qualifying files.
     *
     * For endurance races with multiple qualifying sessions all Qualifying_N.json files are
     * read so drivers who only appear in some sessions are still captured. When cupCategory
     * indicates a sub-class change (0=Pro, 1=Pro-Am, 2=Am, 3=Silver) a new entry_car is
     * found-or-created for the new class rather than updating the existing car, preserving
     * historical results on the old record.
     */
    public function accAssignDrivers(Season $season)
    {
        $worldId = $season->series->world_id;

        $race = CalendarRace::where('season_id', $season->id)
            ->where('is_locked', 0)
            ->orderBy('id', 'asc')
            ->first();

        if (!$race) {
            return back()->withErrors(['acc' => 'No unlocked race found.']);
        }

        $folder = public_path("acc_races/{$race->id}");

        // Aggregate all qualifying sessions into a map: carNumber → [cup_category, drivers]
        // Reading multiple files handles endurance weekends with N qualifying sessions.
        $carDataMap = [];

        for ($n = 1; $n <= 20; $n++) {
            $filePath = "{$folder}/Qualifying_{$n}.json";
            if (!file_exists($filePath)) {
                // First iteration: also try the single-session filename
                if ($n === 1 && file_exists("{$folder}/Qualifying.json")) {
                    $filePath = "{$folder}/Qualifying.json";
                } else {
                    break;
                }
            }

            $raw = file_get_contents($filePath);
            if (substr($raw, 0, 2) === "\xFF\xFE") {
                $raw = substr($raw, 2);
            }
            $raw         = mb_convert_encoding($raw, 'UTF-8', 'UTF-16LE');
            $sessionData = json_decode($raw, true);

            foreach ($sessionData['snapShot']['leaderBoardLines'] ?? [] as $line) {
                $car   = $line['car'];
                $carNo = (string) $car['raceNumber'];

                if (!isset($carDataMap[$carNo])) {
                    $carDataMap[$carNo] = [
                        'cup_category' => $car['cupCategory'] ?? null,
                        'drivers'      => [],
                    ];
                }

                // Merge drivers uniquely across sessions (keyed by full name)
                foreach ($car['drivers'] as $d) {
                    $key = $d['firstName'] . '|' . $d['lastName'];
                    $carDataMap[$carNo]['drivers'][$key] = [
                        'firstName' => $d['firstName'],
                        'lastName'  => $d['lastName'],
                    ];
                }
            }
        }

        if (empty($carDataMap)) {
            return back()->withErrors(['acc' => 'Qualifying file not found.']);
        }

        $season->load(['replaceDriver', 'substituteDriver']);

        // Strip all driver assignments for every car in the season
        $seasonCarIds = \App\Models\EntryCar::whereHas(
            'entryClass.seasonEntry', fn($q) => $q->where('season_id', $season->id)
        )->pluck('id');

        DB::table('entry_car_driver')->whereIn('entry_car_id', $seasonCarIds)->delete();

        $cupCategoryMap = [0 => 'Pro', 1 => 'Pro-Am', 2 => 'Am', 3 => 'Silver'];

        $subClassesByLabel = \App\Models\SeasonClass::where('season_id', $season->id)
            ->whereNotNull('sub_class')
            ->get()
            ->keyBy('sub_class');

        $hasSubClasses = $subClassesByLabel->isNotEmpty();

        foreach ($carDataMap as $carNo => $carData) {
            $cupCategory = $carData['cup_category'];

            $entryCar = \App\Models\EntryCar::with(['entryClass.raceClass'])
                ->where('car_number', $carNo)
                ->whereHas('entryClass.seasonEntry', fn($q) => $q->where('season_id', $season->id))
                ->first();

            if (!$entryCar) {
                continue;
            }

            // When sub-class changes, find/create a new entry_car rather than updating
            // the existing one — this preserves historical results on the old record.
            if ($hasSubClasses && $cupCategory !== null) {
                $targetLabel = $cupCategoryMap[$cupCategory] ?? null;

                if ($targetLabel && $subClassesByLabel->has($targetLabel)) {
                    $targetSeasonClass = $subClassesByLabel[$targetLabel];
                    $currentEntryClass = $entryCar->entryClass;

                    if ($currentEntryClass->race_class_id !== $targetSeasonClass->id) {
                        $newEntryClass = \App\Models\EntryClass::firstOrCreate([
                            'season_entry_id' => $currentEntryClass->season_entry_id,
                            'race_class_id'   => $targetSeasonClass->id,
                        ]);

                        $entryCar = \App\Models\EntryCar::firstOrCreate(
                            [
                                'entry_class_id' => $newEntryClass->id,
                                'car_number'     => $entryCar->car_number,
                            ],
                            [
                                'car_model_id' => $entryCar->car_model_id,
                                'livery_name'  => $entryCar->livery_name,
                            ]
                        );
                    }
                }
            }

            // Assign drivers with substitution rule applied
            foreach ($carData['drivers'] as $d) {
                $driver = Driver::where('world_id', $worldId)
                    ->where('first_name', $d['firstName'])
                    ->where('last_name', $d['lastName'])
                    ->first();

                if (!$driver) {
                    continue;
                }

                if ($season->replaceDriver && $season->substituteDriver
                    && $driver->id === $season->replace_driver_id) {
                    $driver = $season->substituteDriver;
                }

                $entryCar->drivers()->attach($driver->id);
            }
        }

        return back();
    }

    /**
     * Create a season entry (team) inline from the ACC import modal.
     */
    public function accCreateEntry(Request $request, Season $season)
    {
        $request->validate([
            'entrant_id'     => 'required|exists:entrants,id',
            'constructor_id' => 'required|exists:constructors,id',
            'display_name'   => 'nullable|string|max:255',
        ]);

        $season->seasonEntries()->create([
            'entrant_id'     => $request->entrant_id,
            'constructor_id' => $request->constructor_id,
            'display_name'   => $request->display_name ?: null,
            'series_id'      => $season->series_id,
        ]);

        return back();
    }

    public function destroy(Season $season)
    {
        $season->delete();

        return redirect()->route('seasons.index')
            ->with('success', 'Season deleted.');
    }
}