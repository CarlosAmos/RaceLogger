# lib.md — Services & Controllers Reference

> All public methods with signatures. Source: `app/Services/`, `app/Http/Controllers/`

---

## Services (`app/Services/`)

### CareerResultsGridService
**Purpose:** Builds the per-series, per-season results grid shown on the dashboard.

```php
public function getResultsGrid(int $driverId, int $worldId): array
// Returns: [seriesName => ['is_multiclass', 'is_spec', 'seasons' => [year => ['season_id', 'calendar', 'entries']]]]
```

---

### DriverCareerService
**Purpose:** Aggregates a driver's career season-by-season for the career summary table.

```php
public function getCareerStructure($driverId, int $worldId): Collection
// Returns: Collection keyed by year, each value is a Collection of season records

public function aggregateStatsForSeason(int $driverId, int $seasonId): object
// Returns: object { races, wins, podiums, poles, fastest_laps, points, season_active }
```

---

### ResultService
**Purpose:** Saves race and sprint results, calculates class positions, awards points.

```php
public function saveRaceResults(array $data): void
// $data keys: race_id, results[] (entry_car_id, position, status, fastest_lap, laps_completed, gap_*)

public function saveSprintRaceResults(array $data): void
// Same shape as saveRaceResults but targets sprint session
```

---

### QualifyingService
**Purpose:** Saves qualifying session results with full constraint validation.

```php
public function saveQualifying(array $data): void
// $data keys: race_id, qualifying_results[] (entry_car_id, position, best_lap_time_ms)
```

---

### PointsCalculationService
**Purpose:** Calculates and writes championship points after a race result is saved.

```php
public function calculateWeekendPoints($race, array &$results, $sprintRace): void
// Mutates $results in place, adds 'points_awarded' to each result row
```

---

### LapRecordService
**Purpose:** Checks whether a result sets a new track lap record and persists it.

```php
public function checkAndUpdate(Result $result): bool
// Returns true if a new record was set
```

---

### RecordComputeService
**Purpose:** Recomputes all-time driver records (career stats) for a world.

```php
public function compute(int $worldId): array
// Returns: [driver_id => { entries, wins, poles, fastest_laps, podiums, points, race_finishes, championships }]
```

---

### ChampionshipScenarioService
**Purpose:** Calculates title-clinch scenarios for a season.

```php
public function getScenario($seasonId, $entryClassId): mixed
// Returns scenario data for one entry class

public function getClinchTable($seasonId, $seasonClassId): mixed
// Returns full clinch table for a season class
```

---

### CareerSummary
**File:** `app/Services/CareerSummary.php`
> Stub — not yet implemented.

### ResultsGrid
**File:** `app/Services/ResultsGrid.php`
> Stub — not yet implemented.

---

## Controllers (`app/Http/Controllers/`)

### DashboardController
```php
public function index(): View
// Passes: $world, $currentYear, $seasons, $upcomingRaces, $careerMap, $resultsGrid
```

### RaceWeekendController
```php
public function show(CalendarRace $race): View
// Renders races/weekend/manage with full weekend data

public function update(Request $request, CalendarRace $race): RedirectResponse
// Dispatches to ResultService or QualifyingService based on session type
```

### SeasonController
```php
public function show(Season $season): View   // championship standings + season grid
public function store(Request $request): RedirectResponse
public function create(): View
// + standard resource methods
```

### EntryCarController
```php
public function create_entry(...): View      // alternate entry creation flow
public function store_entry(Request $request, ...): RedirectResponse
// + standard resource methods
```

### EntryCarDriverController
```php
public function edit(..., EntryCar $entryCar): View
public function update(Request $request, ..., EntryCar $entryCar): RedirectResponse
```

### SeriesController / WorldController / DriverController / TeamController
> Standard Laravel resource controllers (index, create, store, show, edit, update, destroy).

### LapRecordController
```php
public function index(): View   // world lap records table
```

### Settings Controllers (`app/Http/Controllers/Settings/`)
```php
ProfileController::edit()    // GET  /settings/profile
ProfileController::update()  // PATCH
ProfileController::destroy() // DELETE (with password confirmation)

PasswordController::edit()   // GET  /settings/password
PasswordController::update() // PUT

TwoFactorAuthenticationController::show() // GET /settings/two-factor
```
