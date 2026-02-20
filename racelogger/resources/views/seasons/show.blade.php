@extends('layouts.app')

@section('content')
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
            <a class="nav-link {{ $tab === 'results' ? 'active' : '' }}"
                href="{{ route('seasons.show', [$season, 'tab' => 'results']) }}">
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

    @if($tab === 'results')
    @php
    $races = $season->calendarRaces;
    $rows = collect();

    foreach ($season->seasonEntries as $entry) {
        foreach ($entry->entryClasses as $class) {
            foreach ($class->entryCars as $car) {
                foreach ($car->drivers as $driver) {
                    $rows->push([
                        'driver' => $driver,
                        'team' => $entry->entrant,
                        'entry_car_id' => $car->id,
                    ]);
                }
            }
        }
    }
    @endphp
    <div class="table-responsive">
        <table class="table table-bordered table-sm text-center align-middle">

            <thead class="table-light">
                <tr>
                    <th>Pos</th>
                    <th class="text-start">Driver</th>
                    <th class="text-start">Team</th>

                    @foreach($races as $race)
                    <th>{{ $race->race_code }}</th>
                    @endforeach

                    <th>Points</th>
                </tr>
            </thead>

            <tbody>
                @foreach($rows as $row)

                @php $totalPoints = 0; @endphp

                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td class="text-start">{{ $row['driver']->first_name }} {{ $row['driver']->last_name }}</td>
                    <td class="text-start">{{ $row['team']->name ?? '-' }}</td>

                    @foreach($races as $race)

                    @php
                    $result = $race->results
                    ->firstWhere('entry_car_id', $row['entry_car_id']);
                    @endphp

                    <td>
                        @if($result)
                        {{ $result->position }}
                        @php $totalPoints += $result->points; @endphp
                        @else
                        -
                        @endif
                    </td>

                    @endforeach

                    <td><strong>{{ $totalPoints }}</strong></td>
                </tr>

                @endforeach
            </tbody>

        </table>
    </div>

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