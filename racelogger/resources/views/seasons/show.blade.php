@extends('layouts.app')

@section('content')
<style>
    .dnf_details {
        background: #e5b5fd !important;
        color: black !important;
        font-weight: 500;
    }

    .mini-badge {
        margin-left: 0 !important;
    }

    .cell_style {
        border-bottom: 1px solid #eee !important;
        border-left: 1px solid #eee !important;
    }

    .result-cell {
        border-radius: 0px !important;
    }

    .champ-table {
        border-collapse: collapse;
        font-size: 0.9rem;
        margin-bottom: 10px;
    }

    .champ-table th,
    .champ-table td {
        border: 1px solid #ddd;
        padding: 4px 8px;
        text-align: center;
    }

    .champ-leader-col {
        font-weight: bold;
        background: #f5f5f5;
    }

    .champ-autowin {
        background: #edd174;
        font-weight: 600;
    }

    .champ-win {
        background: #c8f7c5;
        font-weight: 600;
    }

    .champ-next {
        background: #eee;
        color: #777;
    }

    .champ-header {
        background: #222;
        color: white;
        font-weight: 600;
    }
</style>
<div class="container">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1>{{ $season->name }}</h1>
            <p class="mb-0">
                <strong>Year:</strong> {{ $season->year ?? '-' }} |
                <strong>Series:</strong> {{ $season->series->name ?? '-' }}
            </p>
        </div>

        <a href="{{ route('worlds.seasons.edit', [$world, $season]) }}"
            class="btn btn-primary">
            Edit Season
        </a>
    </div>

    {{-- Tabs --}}
    <ul class="nav nav-tabs mb-4">
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'calender' ? 'active' : '' }}"
                href="{{ route('seasons.show', [$season, 'tab' => 'calender']) }}">
                Results
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'teams' ? 'active' : '' }}"
                href="{{ route('seasons.show', [$season, 'tab' => 'teams']) }}">
                Teams
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $tab === 'details' ? 'active' : '' }}"
                href="{{ route('seasons.show', [$season, 'tab' => 'details']) }}">
                Details
            </a>
        </li>
    </ul>
    @if($tab === 'calender')

    @php
    function msToLap($ms) {
    if (!$ms) return '';
    $minutes = floor($ms / 60000);
    $seconds = floor(($ms % 60000) / 1000);
    $milliseconds = $ms % 1000;
    return sprintf('%d:%02d:%03d', $minutes, $seconds, $milliseconds);
    }

    $races = $season->calendarRaces;
    $classTables = [];

    /* Create class shells first */
    foreach ($season->seasonClasses as $seasonClass) {
    $classTables[$seasonClass->id] = [
    'id' => $seasonClass->id,
    'name' => $seasonClass->name,
    'display_order' => $seasonClass->display_order ?? 0,
    'rows' => []
    ];
    }

    /* Populate from results */
    foreach ($races as $race) {

    foreach ($race->results as $result) {

    $entryCar = $race->entryCars
    ->firstWhere('id', $result->entry_car_id);

    if (!$entryCar) continue;

    $classId = $entryCar->entryClass->race_class_id;

    if (!isset($classTables[$classId])) continue;

    foreach ($result->resultDrivers ?? [] as $resultDriver) {

    $driver = $resultDriver->driver;
    if (!$driver) continue;

    $driverId = $driver->id;

    if (!isset($classTables[$classId]['rows'][$driverId])) {
    $classTables[$classId]['rows'][$driverId] = [
    'driver' => $driver,
    'team' => $entryCar->entryClass->seasonEntry->entrant ?? null,
    'car_number' => $entryCar->car_number,
    'raceResults' => [],
    'totalPoints' => 0
    ];
    }

    $classTables[$classId]['rows'][$driverId]['raceResults'][$race->id] = $result;
    $classTables[$classId]['rows'][$driverId]['totalPoints'] += $result->points_awarded;
    }
    }
    }

    $classTables = collect($classTables)
    ->sortBy('display_order')
    ->values();
    @endphp

    <div style="display:flex;justify-content: center;">
        @if(count($classTables) > 0)
            <h4 class="fw-bold">Potential Champion{{count($classTables) > 1 ? "s" : ""}}</h4>
        @endif
    </div>
    <div style="display:flex;justify-content: space-evenly;">
        @foreach($classTables as $class)
            @if(isset($classScenarios[$class['id']]))
            <div style="display:flex; align-items:center; flex-direction:column;">                
                <h4 class="mt-2 mb-3 text-uppercase border-bottom pb-2">
                    {{ $class['name'] }}
                </h4>
                
                @php
                $scenario = $classScenarios[$class['id']];
                @endphp


                <h5 style="font-style:italic;">How can #{{$scenario['leader']->entryCar->car_number}} become champion</h5>
                <table class="champ-table">

                    <thead>
                        <tr>

                            <th>
                                Leader (#{{ $scenario['leader']->entryCar->car_number }})
                            </th>

                            @foreach($scenario['rivals'] as $rival)

                            <th>
                                #{{ $rival->entryCar->car_number }} needs to be
                            </th>

                            @endforeach

                        </tr>
                    </thead>

                    <tbody>

                        @foreach($scenario['rows'] as $row)

                        <tr>

                            <td class="champ-leader-col">
                                P{{ $row['leader_pos'] }}
                            </td>

                            @if(isset($row['next_race']))

                            <td colspan="{{ count($scenario['rivals']) }}">
                                Go to next race
                            </td>

                            @else

                            @foreach($scenario['rivals'] as $rival)

                            <td
                                @if(isset($row['next_race']))

                                @endif


                                @php
                                $pos=$row['rivals'][$rival->entry_car_id] ?? null;
                                @endphp

                                @if($pos)
                                @if($pos > 10)
                                class="champ-next">Next Race
                                @else
                                class="champ-win">{{ $pos }}{{ ['st','nd','rd','th'][$pos-1] ?? 'th' }} {{ ($pos > 1 ? "or lower" : "") }}
                                @endif
                                @else
                                class="champ-autowin">—
                                @endif

                            </td>

                            @endforeach

                            @endif

                        </tr>

                        @endforeach

                    </tbody>
                </table>
            </div>
            @endif
        @endforeach
    </div>

    @foreach($classTables as $class)

    @php
    $currentClassId = $class['id'];

    $sortedRows = collect($class['rows'] ?? [])
    ->sortByDesc('totalPoints')
    ->values();
    @endphp

    <h4 class="mt-2 mb-3 text-uppercase fw-bold border-bottom pb-2">
        {{ $class['name'] }}
    </h4>


    @if(isset($classScenarios[$class['id']]))


    @endif

    <div class="table-responsive">
        <table class="table season-standings text-center align-middle">

            <thead>
                <tr>
                    <th>Pos</th>
                    <th class="text-start">Driver</th>
                    <th>No.</th>
                    <th class="text-start">Team</th>

                    @foreach($races as $race)
                    <th>
                        <a href="{{ route('races.show', $race) }}"
                            class="race-header-link"
                            title="{{ $race->name ?? '' }}">
                            {{ $race->race_code }}
                        </a>
                    </th>
                    @endforeach

                    <th>Pts</th>
                </tr>
            </thead>

            <tbody>
                @php
                $sortedRows = collect($class['rows'] ?? [])
                ->sortByDesc('totalPoints')
                ->values();
                @endphp
                @foreach($sortedRows as $row)

                <tr class="{{ $loop->first ? 'leader-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>

                    <td class="text-start">
                        {{ $row['driver']->first_name }}
                        {{ $row['driver']->last_name }}
                    </td>

                    <td>#{{ $row['car_number'] }}</td>

                    <td class="text-start text-muted">
                        {{ $row['team']->name ?? '' }}
                    </td>

                    @foreach($races as $race)

                    @php
                    $result = $row['raceResults'][$race->id] ?? null;

                    $cellClass = '';
                    $pBadge = false;
                    $flBadge = false;

                    if ($result) {

                    if (is_numeric($result->class_position) && $result->status === "finished") {
                    if ($result->class_position == 1) {
                    $cellClass = 'bg-warning text-dark';
                    } elseif ($result->class_position == 2) {
                    $cellClass = 'bg-secondary text-white';
                    } elseif ($result->class_position == 3) {
                    $cellClass = 'bg-bronze text-white';
                    } elseif ($result->points_awarded > 0) {
                    $cellClass = 'bg-success text-white';
                    }
                    } else {
                    switch (strtoupper($result->status)) {
                    case 'DSQ':
                    $cellClass = 'bg-dark text-white';
                    break;
                    case 'RET':
                    case 'DNF':
                    $cellClass = 'dnf_details text-white';
                    break;
                    case 'DNS':
                    $cellClass = 'bg-white text-dark';
                    break;
                    case 'DNQ':
                    case 'DNPQ':
                    $cellClass = 'bg-danger text-white';
                    break;
                    }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Class-Based Pole Detection
                    |--------------------------------------------------------------------------
                    */
                    $finalSession = $race->qualifyingSessions
                    ->sortByDesc('session_order')
                    ->first();

                    if ($finalSession) {

                    $classPole = $finalSession->results
                    ->filter(function ($qResult) use ($race, $currentClassId) {

                    $entryCar = $race->entryCars
                    ->firstWhere('id', $qResult->entry_car_id);

                    return $entryCar
                    && $entryCar->entryClass->race_class_id == $currentClassId;
                    })
                    ->sortBy('position')
                    ->first();

                    if ($classPole && $classPole->entry_car_id == $result->entry_car_id) {
                    $pBadge = true;
                    }
                    }

                    if ($result->fastest_lap) {
                    $flBadge = true;
                    }
                    }
                    @endphp

                    <td class="{{ $cellClass }} result-cell cell_style">
                        @if($result)
                        @if($result->status == "finished") {{ $result->class_position }}
                        @else {{ strtoupper($result->status) }}
                        @endif
                        @if($pBadge)
                        <span class="mini-badge badge-p">P</span>
                        @endif

                        @if($flBadge)
                        <span class="mini-badge badge-fl">FL</span>
                        @endif
                        @else

                        @endif
                    </td>

                    @endforeach

                    <td class="fw-bold cell_style">{{ $row['totalPoints'] }}</td>

                </tr>
                @endforeach


                {{-- Pole Position Row --}}
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-start">Pole Position</td>

                    @foreach($races as $race)

                    @php
                    $finalSession = $race->qualifyingSessions
                    ->sortByDesc('session_order')
                    ->first();

                    $pole = null;

                    if ($finalSession) {
                    $pole = $finalSession->results
                    ->filter(function ($result) use ($race, $currentClassId) {

                    $entryCar = $race->entryCars
                    ->firstWhere('id', $result->entry_car_id);

                    return $entryCar
                    && $entryCar->entryClass->race_class_id == $currentClassId;
                    })
                    ->sortBy('position')
                    ->first();
                    }
                    @endphp

                    <td>
                        @if($pole)
                        <div>#{{ $pole->entryCar->car_number }}</div>
                        <small class="text-muted">
                            {{ msToLap($pole->best_lap_time_ms) }}
                        </small>
                        @else
                        -
                        @endif
                    </td>

                    @endforeach

                    <td></td>
                </tr>


                {{-- Fastest Lap Row --}}
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-start">Fastest Lap</td>

                    @foreach($races as $race)

                    @php
                    $fastest = $race->results
                    ->filter(function ($result) use ($race, $currentClassId) {

                    $entryCar = $race->entryCars
                    ->firstWhere('id', $result->entry_car_id);

                    return $entryCar
                    && $entryCar->entryClass->race_class_id == $currentClassId
                    && $result->fastest_lap;
                    })
                    ->first();
                    @endphp

                    <td>
                        @if($fastest)
                        <div>#{{ $fastest->entryCar->car_number }}</div>
                        <small class="text-muted">
                            {{ msToLap($fastest->fastest_lap_time_ms) }}
                        </small>
                        @else
                        -
                        @endif
                    </td>

                    @endforeach

                    <td></td>
                </tr>

            </tbody>
        </table>
    </div>

    @endforeach

    {{-- ========================= --}}
    {{-- TEAM CHAMPIONSHIP TABLE --}}
    {{-- ========================= --}}
    @foreach($classTables as $class)
    @php
    $currentClassId = $class['id'];
    $className = $class['name'];
    switch($className){
    case "Hypercar":
    $className = "Hypercar World Endurance Manufacturer's Championship";
    $teamScoringMode = 'best_car';
    break;
    case "LMP2":
    case "LMGTE Am":
    case "GT3":
    $className = "FIA Endurance Trophy for ".$className." Teams";
    $teamScoringMode = 'per_car';
    break;
    }


    $teamRows = [];

    foreach ($races as $race) {

    $classResults = $race->results->filter(function ($result) use ($race, $currentClassId) {

    $entryCar = $race->entryCars
    ->firstWhere('id', $result->entry_car_id);

    return $entryCar
    && $entryCar->entryClass->race_class_id == $currentClassId;
    });

    if ($teamScoringMode === 'best_car') {
    /*
    |--------------------------------------------------------------------------
    | Hypercar Style (Best Car Only Per Team)
    |--------------------------------------------------------------------------
    */

    $groupedByTeam = $classResults->groupBy(function ($result) use ($race) {

    $entryCar = $race->entryCars
    ->firstWhere('id', $result->entry_car_id);

    return optional($entryCar->entryClass->seasonEntry->entrant)->id;
    });

    foreach ($groupedByTeam as $teamId => $results) {

    $bestResult = $results
    ->filter(fn($r) => is_numeric($r->class_position))
    ->sortBy('class_position')
    ->first();

    if (!$bestResult) continue;

    $entryCar = $race->entryCars
    ->firstWhere('id', $bestResult->entry_car_id);

    $seasonEntry = $entryCar->entryClass->seasonEntry;
    $entrant = $seasonEntry->entrant;
    $constructor = $seasonEntry->constructor;

    $displayLabel = ($className === 'Hypercar'
    ? optional($constructor)->name
    : optional($entrant)->name);

    if (!isset($teamRows[$teamId])) {
    $teamRows[$teamId] = [
    'label' => $displayLabel,
    'raceResults' => [],
    'totalPoints' => 0
    ];
    }

    $teamRows[$teamId]['raceResults'][$race->id] = $bestResult;
    $teamRows[$teamId]['totalPoints'] += $bestResult->points_awarded;
    }

    } else {

    /*
    |--------------------------------------------------------------------------
    | Per Car Style (LMP2 / LMGTE / GT3)
    |--------------------------------------------------------------------------
    */

    foreach ($classResults as $result) {

    if (!is_numeric($result->class_position)) continue;

    $entryCar = $race->entryCars
    ->firstWhere('id', $result->entry_car_id);

    $team = $entryCar->entryClass->seasonEntry->entrant;
    $carKey = $entryCar->id;

    if (!isset($teamRows[$carKey])) {
    $teamRows[$carKey] = [
    'label' => '#'.$entryCar->car_number.' '.$team->name ,
    'raceResults' => [],
    'totalPoints' => 0
    ];
    }

    $teamRows[$carKey]['raceResults'][$race->id] = $result;
    $teamRows[$carKey]['totalPoints'] += $result->points_awarded;
    }
    }
    }

    $teamRows = collect($teamRows)
    ->sortByDesc('totalPoints')
    ->values();
    @endphp


    <h5 class="mt-5 mb-3 fw-bold border-bottom pb-2">
        {{ $className }}
    </h5>

    <div class="table-responsive">
        <table class="table season-standings text-center align-middle">

            <thead>
                <tr>
                    <th>Pos</th>
                    <th class="text-start">Team</th>

                    @foreach($races as $race)
                    <th style="cursor:default;user-select:none;">{{ $race->race_code }}</th>
                    @endforeach

                    <th>Pts</th>
                </tr>
            </thead>

            <tbody>

                @foreach($teamRows as $teamRow)

                <tr class="{{ $loop->first ? 'leader-row' : '' }}">
                    <td>{{ $loop->iteration }}</td>

                    <td class="text-start">
                        {{ $teamRow['label'] }}
                    </td>

                    @foreach($races as $race)

                    @php
                    $result = $teamRow['raceResults'][$race->id] ?? null;

                    $cellClass = '';

                    if ($result && is_numeric($result->class_position)) {

                    if ($result->class_position == 1) {
                    $cellClass = 'bg-warning text-dark';
                    } elseif ($result->class_position == 2) {
                    $cellClass = 'bg-secondary text-white';
                    } elseif ($result->class_position == 3) {
                    $cellClass = 'bg-bronze text-white';
                    } elseif ($result->points_awarded > 0) {
                    $cellClass = 'bg-success text-white';
                    }
                    }
                    @endphp

                    <td class="{{ $cellClass }} result-cell cell_style">
                        {{ $result->class_position ?? '' }}
                    </td>

                    @endforeach

                    <td class="fw-bold cell_style">{{ $teamRow['totalPoints'] }}</td>

                </tr>

                @endforeach

            </tbody>
        </table>
    </div>
    @endforeach
    @endif


    @if($tab === 'details')

    {{-- EXISTING TEAMS CONTENT GOES HERE --}}


    {{-- Teams Section --}}
    <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">

        @foreach($season->seasonEntries as $entry)

        <div class="col">

            <div class="card h-100 shadow-sm border-0">

                <div class="card-body d-flex flex-column">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-1">
                                {{ $entry->display_name ?? $entry->entrant->name }}
                            </h6>

                            @if($entry->display_name)
                            <small class="text-muted">
                                {{ $entry->entrant->name }}
                            </small>
                            @endif
                        </div>

                        <span class="badge bg-secondary">
                            {{ $entry->constructor->name }}
                        </span>
                    </div>

                    <hr class="my-2">

                    {{-- Classes --}}
                    <div class="mb-2">

                        @forelse($entry->entryClasses as $class)

                        <div class="d-flex justify-content-between align-items-center mb-1">

                            <span class="badge bg-primary">
                                {{ $class->raceClass->name }}
                            </span>

                            <a href="{{ route(
                                    'worlds.seasons.season-entries.entry-classes.entry-cars.index',
                                    [$world, $season, $entry, $class]
                                ) }}"
                                class="btn btn-sm btn-outline-secondary">
                                Manage Cars
                            </a>

                        </div>

                        @empty
                        <span class="text-muted small">
                            No classes
                        </span>
                        @endforelse
                    </div>

                    {{-- Add Class Form --}}
                    <form method="POST"
                        action="{{ route('worlds.seasons.season-entries.entry-classes.store',
                            [$world, $season, $entry]) }}"
                        class="mt-auto">
                        @csrf

                        <div class="input-group input-group-sm">
                            <select name="race_class_id"
                                class="form-select"
                                required>
                                <option value="">Add Class</option>

                                @foreach($season->seasonClasses as $seasonClass)
                                <option value="{{ $seasonClass->id }}">
                                    {{ $seasonClass->name }}
                                </option>
                                @endforeach
                            </select>

                            <button class="btn btn-outline-primary">
                                Add
                            </button>
                        </div>
                    </form>

                </div>

            </div>

        </div>

        @endforeach

    </div>

    @endif
    @if($tab === 'teams')

    @foreach($season->seasonClasses as $seasonClass)

    <div class="mb-5">

        <h4 class="mb-3 text-uppercase fw-bold border-bottom pb-2">
            {{ $seasonClass->name }}
        </h4>

        @php
        $groupedCars = [];
        @endphp

        {{-- Collect & Group Cars --}}
        @foreach($season->seasonEntries as $seasonEntry)
        @foreach($seasonEntry->entryClasses as $entryClass)

        @if($entryClass->race_class_id === $seasonClass->id)

        @foreach($entryClass->entryCars as $car)

        @php
        $groupKey = $seasonEntry->id . '_' .
        $car->carModel->id . '_' .
        optional($car->carModel->engine)->id;

        if (!isset($groupedCars[$groupKey])) {
        $groupedCars[$groupKey] = [
        'car_model' => $car->carModel,
        'engine' => $car->carModel->engine,
        'cars' => [],
        ];
        }

        $groupedCars[$groupKey]['cars'][] = $car;
        @endphp

        @endforeach

        @endif

        @endforeach
        @endforeach


        <div class="card">
            <div class="card-body p-0">

                <table class="table table-bordered mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Entrant</th>
                            <th>Car</th>
                            <th>Engine</th>
                            <th>Hybrid</th>
                            <th>No.</th>
                            <th>Drivers</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($groupedCars as $group)

                        @php
                        $cars = collect($group['cars'])->sortBy('car_number');
                        $rowCount = $cars->count();

                        // Get display names for all cars in this group
                        $displayNames = $cars->map(function($car) {
                        return $car->livery_name
                        ?? $car->entryClass->seasonEntry->entrant->name;
                        })->unique();

                        $mergeEntrant = $displayNames->count() === 1;
                        $commonName = $mergeEntrant ? $displayNames->first() : null;
                        @endphp

                        @foreach($cars as $index => $car)

                        @php
                        $displayName = $car->livery_name
                        ?? $car->entryClass->seasonEntry->entrant->name;
                        @endphp

                        <tr>

                            {{-- Entrant Column --}}
                            @if($mergeEntrant)

                            @if($index === 0)
                            <td rowspan="{{ $rowCount }}"
                                class="align-middle fw-bold">
                                {{ $commonName }}
                            </td>
                            @endif

                            @else

                            <td class="fw-bold">
                                {{ $displayName }}
                            </td>

                            @endif


                            {{-- Car / Engine / Hybrid (always merged) --}}
                            @if($index === 0)

                            <td rowspan="{{ $rowCount }}" class="align-middle">
                                {{ $group['car_model']->name }}
                            </td>

                            <td rowspan="{{ $rowCount }}" class="align-middle">
                                {{ $group['engine']->name ?? '-' }}
                            </td>

                            <td rowspan="{{ $rowCount }}" class="align-middle">
                                {{ $group['car_model']->hybrid ? 'Hybrid' : '-' }}
                            </td>

                            @endif

                            {{-- Car Number --}}
                            <td>
                                <span class="badge bg-dark">
                                    {{ $car->car_number }}
                                </span>
                            </td>

                            {{-- Drivers --}}
                            <td>
                                @foreach($car->drivers as $driver)
                                <div>{{ $driver->full_name }}</div>
                                @endforeach
                            </td>

                        </tr>

                        @endforeach

                        @endforeach

                    </tbody>
                </table>

            </div>
        </div>

    </div>

    @endforeach

    @endif

</div>
@endsection