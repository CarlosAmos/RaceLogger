@php
    $raceCars = $race->entryCars()
        ->with([
            'entryClass.raceClass',
            'entryClass.seasonEntry.entrant',
            'carModel',
            'drivers'
        ])
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

    $totalPositions = $raceCars->count();

    $savedResults = $activeRaceSession->results
        ->sortBy('position')
        ->values()
        ->keyBy('position');


    function formatGap($result) {
        if ($result->gap_laps_down) {
            return '+' . $result->gap_laps_down . 'L';
        }

        if ($result->gap_to_leader_ms) {
            return msToLap($result->gap_to_leader_ms);
        }

        return null;
    }
@endphp

<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-4">Race Results</h5>

@if($raceCars->isEmpty())
    <div class="alert alert-warning">
        No participants selected for this race.
    </div>
@endif

<input type="hidden" name="race_session_id" value="{{ $activeRaceSession->id }}">

<div class="table-responsive">
<table class="table table-bordered table-sm align-middle text-center">

<thead class="table-light">
<tr>
    <th style="width:70px;">Pos</th>
    <th>Car</th>
    <th style="width:120px;">Status</th>
    <th style="width:90px;">Laps</th>
    <th style="width:160px;">Gap</th>
    <th style="width:160px;">Fastest Lap</th>
</tr>
</thead>

<tbody>

@for($pos = 1; $pos <= $totalPositions; $pos++)

@php
    $existing = $savedResults[$pos] ?? null;
@endphp

<tr>

    {{-- Position --}}
    <td class="fw-bold">
        {{ $pos }}
        <input type="hidden"
               name="results[{{ $pos-1 }}][position]"
               value="{{ $pos }}">
    </td>

    {{-- Car --}}
    <td>
        <select name="results[{{ $pos-1 }}][entry_car_id]"
                class="form-select form-select-sm race-select">

            <option value="">-- Select Car --</option>

            @foreach($classGroups as $group)

                <optgroup label="{{ $group['name'] }}">

                    @foreach($group['cars'] as $car)

                        @php
                            $entrant = $car->entryClass->seasonEntry->entrant->name ?? '';
                            $displayName = $car->livery_name ?? $entrant;
                            $carModel = $car->carModel->name ?? '';

                            $carDrivers = $car->drivers;
                            $carDriverList = [];
                            foreach($carDrivers as $drivers => $driver) {
                                 $carDriverList[] = $driver->first_name." ".$driver->last_name;
                            }

                            $label = "#{$car->car_number} {$displayName}";
                            if($carModel) $label .= " ({$carModel}) - ".implode(", ",$carDriverList);
                        @endphp

                        <option value="{{ $car->id }}"
                            @selected($existing && $existing->entry_car_id == $car->id)>
                            {{ $label }}
                        </option>

                    @endforeach

                </optgroup>

            @endforeach

        </select>
    </td>

    {{-- Status --}}
    <td>
        <select name="results[{{ $pos-1 }}][status]"
                class="form-select form-select-sm">

            @foreach(['finished','dnf','dsq','dns'] as $status)
                <option value="{{ $status }}"
                    @selected($existing && $existing->status === $status)>
                    {{ strtoupper($status) }}
                </option>
            @endforeach

        </select>
    </td>

    {{-- Laps --}}
    <td>
        <input type="number"
               name="results[{{ $pos-1 }}][laps_completed]"
               class="form-control form-control-sm"
               value="{{ $existing->laps_completed ?? '' }}">
    </td>

    {{-- Gap --}}
    <td>
        <input type="text"
               name="results[{{ $pos-1 }}][gap]"
               class="form-control form-control-sm gap-input"
               placeholder="0:00:000 or +1L"
               value="{{ $existing ? formatGap($existing) : '' }}">
    </td>

    {{-- Fastest Lap --}}
    <td>
        <input type="text"
               name="results[{{ $pos-1 }}][fastest_lap]"
               class="form-control form-control-sm lap-time-input"
               placeholder="0:00:000"
               value="{{ $existing ? msToLap($existing->fastest_lap_time_ms) : '' }}">
    </td>

</tr>

@endfor

</tbody>
</table>
</div>

</div>
</div>

{{-- JavaScript --}}
<script>
document.addEventListener('DOMContentLoaded', function () {

    // Prevent duplicate car selection
    const selects = document.querySelectorAll('.race-select');

    selects.forEach(select => {
        select.addEventListener('change', function () {

            const selectedValues = Array.from(selects)
                .map(s => s.value)
                .filter(v => v !== '');

            selects.forEach(s => {
                Array.from(s.options).forEach(option => {
                    if (option.value === '') return;

                    option.disabled =
                        selectedValues.includes(option.value)
                        && s.value !== option.value;
                });
            });
        });
    });

    // Lap formatting
    document.querySelectorAll('.lap-time-input')
        .forEach(input => {

            input.addEventListener('input', function () {

                let value = input.value.replace(/\D/g, '');
                if (value.length > 6) value = value.substring(0, 6);

                let formatted = '';

                if (value.length <= 1) {
                    formatted = value;
                }
                else if (value.length <= 3) {
                    formatted = value.substring(0,1) + ':' + value.substring(1);
                }
                else {
                    formatted =
                        value.substring(0,1) + ':' +
                        value.substring(1,3) + ':' +
                        value.substring(3);
                }

                input.value = formatted;
            });
        });

    // Gap formatting
    document.querySelectorAll('.gap-input')
        .forEach(input => {

            input.addEventListener('input', function () {

                const raw = input.value;

                if (raw.startsWith('+')) {
                    input.value = raw
                        .replace(/[^+\dL]/gi, '')
                        .toUpperCase();
                    return;
                }

                let digits = raw.replace(/\D/g, '');
                if (digits.length > 6) digits = digits.substring(0, 6);

                let formatted = '';

                if (digits.length <= 1) {
                    formatted = digits;
                }
                else if (digits.length <= 3) {
                    formatted = digits.substring(0,1) + ':' + digits.substring(1);
                }
                else {
                    formatted =
                        digits.substring(0,1) + ':' +
                        digits.substring(1,3) + ':' +
                        digits.substring(3);
                }

                input.value = formatted;
            });
        });

});
</script>