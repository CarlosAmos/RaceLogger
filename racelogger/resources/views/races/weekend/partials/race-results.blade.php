@php
    $raceCars = $race->entryCars()
        ->with('drivers', 'entryClass.raceClass', 'entryClass.seasonEntry.entrant')
        ->get();

    $classGroups = $raceCars->groupBy(function($car) {
        return $car->entryClass->raceClass->id;
    })->map(function($cars) {
        return [
            'name' => $cars->first()->entryClass->raceClass->name,
            'display_order' => $cars->first()->entryClass->raceClass->display_order ?? 0,
            'cars' => $cars->sortBy('car_number')->values()
        ];
    })->sortBy('display_order')->values();

    $existingResults = $race->results;
    $multipleClasses = $classGroups->count() > 1;
@endphp

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="mb-4">Race Results</h5>

        @if($raceCars->isEmpty())
            <div class="alert alert-warning">
                No participants selected for this race.
            </div>
        @endif

        @foreach($classGroups as $group)

            @if($multipleClasses)
                <h6 class="fw-bold text-uppercase mb-3 border-bottom pb-2">
                    {{ $group['name'] }}
                </h6>
            @endif

            <div class="table-responsive mb-4">
                <table class="table table-bordered table-sm align-middle text-center">

                    <thead class="table-light">
                        <tr>
                            <th>Car</th>
                            <th>Position</th>
                            <th>Status</th>
                            <th>Laps</th>
                            <th>Gap (ms)</th>
                            <th>Fastest Lap (ms)</th>
                            <th class="text-start">Drivers</th>
                        </tr>
                    </thead>

                    <tbody>

                        @foreach($group['cars'] as $index => $car)

                            @php
                                $existing = $existingResults
                                    ->firstWhere('entry_car_id', $car->id);
                            @endphp

                            <tr>
                                <td class="text-start">
                                    <strong>#{{ $car->car_number }}</strong>
                                    {{ $car->livery_name
                                        ?? $car->entryClass->seasonEntry->entrant->name }}

                                    <input type="hidden"
                                           name="results[{{ $loop->parent->index }}][entry_car_id]"
                                           value="{{ $car->id }}">
                                </td>

                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="results[{{ $loop->parent->index }}][position]"
                                           value="{{ $existing->position ?? '' }}">
                                </td>

                                <td>
                                    <select class="form-select form-select-sm"
                                            name="results[{{ $loop->parent->index }}][status]">
                                        @foreach(['finished','dnf','dsq','dns'] as $status)
                                            <option value="{{ $status }}"
                                                @selected(($existing->status ?? 'finished') === $status)>
                                                {{ strtoupper($status) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>

                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="results[{{ $loop->parent->index }}][laps_completed]"
                                           value="{{ $existing->laps_completed ?? 0 }}">
                                </td>

                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="results[{{ $loop->parent->index }}][gap_to_leader_ms]"
                                           value="{{ $existing->gap_to_leader_ms ?? '' }}">
                                </td>

                                <td>
                                    <input type="number"
                                           class="form-control form-control-sm"
                                           name="results[{{ $loop->parent->index }}][fastest_lap_time_ms]"
                                           value="{{ $existing->fastest_lap_time_ms ?? '' }}">
                                </td>

                                <td class="text-start">
                                    @foreach($car->drivers as $driver)

                                        @php
                                            $isChecked = $existing
                                                ? $existing->drivers
                                                    ->contains('driver_id', $driver->id)
                                                : true;
                                        @endphp

                                        <div class="form-check">
                                            <input class="form-check-input"
                                                   type="checkbox"
                                                   name="results[{{ $loop->parent->parent->index }}][drivers][]"
                                                   value="{{ $driver->id }}"
                                                   @checked($isChecked)>

                                            <label class="form-check-label">
                                                {{ $driver->full_name }}
                                            </label>
                                        </div>

                                    @endforeach
                                </td>
                            </tr>

                        @endforeach

                    </tbody>
                </table>
            </div>

        @endforeach

    </div>
</div>