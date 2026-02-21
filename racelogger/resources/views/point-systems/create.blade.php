@extends('layouts.app')

@section('content')

<div class="container">
    <h2>Create Point System</h2>

    @if ($errors->any())
        <div style="background:#f8d7da; padding:10px; margin-bottom:15px;">
            <ul style="margin:0;">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('point-systems.store') }}">
        @csrf
        @if(request('season_id'))
            <input type="hidden"
                name="season_id"
                value="{{ request('season_id') }}">
        @endif
        {{-- ========================= --}}
        {{-- BASIC INFO --}}
        {{-- ========================= --}}
        <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-4">
            <label class="form-label">Description</label>
            <textarea name="description"
                      class="form-control"></textarea>
        </div>

        <hr>

        {{-- ========================= --}}
        {{-- RACE POINTS --}}
        {{-- ========================= --}}
        <h4>Race Points</h4>

        <table class="table table-bordered" id="racePointsTable">
            <thead>
                <tr>
                    <th>Position</th>
                    <th>Points</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 10; $i++)
                <tr>
                    <td class="race-position-number">{{ $i }}</td>
                    <td>
                        <input type="number"
                               name="race_points[{{ $i }}]"
                               class="form-control"
                               min="0">
                    </td>
                    <td>
                        <button type="button"
                                class="btn btn-sm btn-danger remove-race-row">
                            ✕
                        </button>
                    </td>
                </tr>
                @endfor
            </tbody>
        </table>

        <button type="button"
                class="btn btn-sm btn-primary mb-4"
                id="addRaceRow">
            Add Position
        </button>

        <hr>

        {{-- ========================= --}}
        {{-- QUALIFYING POINTS --}}
        {{-- ========================= --}}
        <div class="form-check mb-3">
            <input class="form-check-input"
                   type="checkbox"
                   id="enableQualifying"
                   name="enable_qualifying">
            <label class="form-check-label">
                Enable Qualifying Points
            </label>
        </div>

        <div id="qualifyingSection" style="display:none;">

            <table class="table table-bordered" id="qualifyingPointsTable">
                <thead>
                    <tr>
                        <th>Qualifying Position</th>
                        <th>Points</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="qual-position-number">1</td>
                        <td>
                            <input type="number"
                                   name="qualifying_points[1]"
                                   class="form-control"
                                   min="0">
                        </td>
                        <td>
                            <button type="button"
                                    class="btn btn-sm btn-danger remove-qual-row">
                                ✕
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>

            <button type="button"
                    class="btn btn-sm btn-primary mb-4"
                    id="addQualRow">
                Add Qualifying Position
            </button>
        </div>

        <hr>

        {{-- ========================= --}}
        {{-- FASTEST LAP --}}
        {{-- ========================= --}}
        <div class="form-check mb-3">
            <input class="form-check-input"
                   type="checkbox"
                   id="enableFastestLap"
                   name="enable_fastest_lap">
            <label class="form-check-label">
                Award Fastest Lap Points
            </label>
        </div>

        <div id="fastestLapSection" style="display:none;">
            <label class="form-label">Fastest Lap Points</label>
            <input type="number"
                   name="fastest_lap_points"
                   class="form-control"
                   min="0">
        </div>

        <hr>

        <button type="submit" class="btn btn-success">
            Create Point System
        </button>

    </form>
</div>

@endsection

@section('scripts')
<script>

/* =========================
   SHOW / HIDE SECTIONS
========================= */

document.getElementById('enableQualifying')
.addEventListener('change', function() {
    document.getElementById('qualifyingSection').style.display =
        this.checked ? 'block' : 'none';
});

document.getElementById('enableFastestLap')
.addEventListener('change', function() {
    document.getElementById('fastestLapSection').style.display =
        this.checked ? 'block' : 'none';
});

/* =========================
   RACE ROWS
========================= */

document.getElementById('addRaceRow')
.addEventListener('click', function() {

    const table = document
        .getElementById('racePointsTable')
        .querySelector('tbody');

    const newPosition = table.rows.length + 1;

    const row = table.insertRow();

    row.innerHTML = `
        <td class="race-position-number">${newPosition}</td>
        <td>
            <input type="number"
                   name="race_points[${newPosition}]"
                   class="form-control"
                   min="0">
        </td>
        <td>
            <button type="button"
                    class="btn btn-sm btn-danger remove-race-row">
                ✕
            </button>
        </td>
    `;
});

document.addEventListener('click', function(e) {

    if (e.target.classList.contains('remove-race-row')) {

        e.target.closest('tr').remove();

        const rows = document
            .getElementById('racePointsTable')
            .querySelectorAll('tbody tr');

        rows.forEach((row, index) => {

            const position = index + 1;

            row.querySelector('.race-position-number')
                .innerText = position;

            row.querySelector('input')
                .setAttribute('name',
                    `race_points[${position}]`);
        });
    }

});

/* =========================
   QUALIFYING ROWS
========================= */

document.getElementById('addQualRow')
.addEventListener('click', function() {

    const table = document
        .getElementById('qualifyingPointsTable')
        .querySelector('tbody');

    const newPosition = table.rows.length + 1;

    const row = table.insertRow();

    row.innerHTML = `
        <td class="qual-position-number">${newPosition}</td>
        <td>
            <input type="number"
                   name="qualifying_points[${newPosition}]"
                   class="form-control"
                   min="0">
        </td>
        <td>
            <button type="button"
                    class="btn btn-sm btn-danger remove-qual-row">
                ✕
            </button>
        </td>
    `;
});

document.addEventListener('click', function(e) {

    if (e.target.classList.contains('remove-qual-row')) {

        e.target.closest('tr').remove();

        const rows = document
            .getElementById('qualifyingPointsTable')
            .querySelectorAll('tbody tr');

        rows.forEach((row, index) => {

            const position = index + 1;

            row.querySelector('.qual-position-number')
                .innerText = position;

            row.querySelector('input')
                .setAttribute('name',
                    `qualifying_points[${position}]`);
        });
    }

});

</script>
@endsection