@php
    $raceCars = $race->entryCars()
        ->with([
            'entryClass.raceClass',
            'entryClass.seasonEntry.entrant',
            'carModel'
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
@endphp
@php
    $savedSessions = $race->qualifyingSessions
        ->sortBy('session_order')
        ->map(function ($session) {
            return [
                'session_order' => $session->session_order,
                'name' => $session->name,
                'is_elimination' => $session->is_elimination,
                'final_target' => $session->final_target,
                'results' => $session->results->map(function ($result) {
                    return [
                        'entry_car_id' => $result->entry_car_id,
                        'position' => $result->position,
                        'best_lap' => msToLap($result->best_lap_time_ms),
                    ];
                })->values()
            ];
        })->values();
@endphp
@php
    function msToLap($ms) {
        if (!$ms) return null;

        $minutes = floor($ms / 60000);
        $seconds = floor(($ms % 60000) / 1000);
        $milliseconds = $ms % 1000;

        return sprintf('%d:%02d:%03d', $minutes, $seconds, $milliseconds);
    }
@endphp

<div class="card shadow-sm">
<div class="card-body">

<h5 class="mb-4">Qualifying Setup</h5>

<input type="hidden" name="qualifying[format]" id="format-hidden">
<input type="hidden" name="qualifying[elimination_enabled]" id="elimination-hidden">
<input type="hidden" name="qualifying[final_target]" id="target-hidden">

<div class="row mb-4">

    <div class="col-md-3">
        <label class="form-label fw-bold">Qualifying Format</label>
        <select id="qualifying-format" class="form-select">
            <option value="1">Single Session</option>
            <option value="2">Q1 + Q2</option>
            <option value="3">Q1 + Q2 + Q3</option>
            <option value="4">Q1 + Q2 + Q3 + Q4</option>
        </select>
    </div>

    <div class="col-md-3 d-flex align-items-end">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="enable-elimination">
            <label class="form-check-label fw-bold">
                Enable Elimination Format
            </label>
        </div>
    </div>

    <div class="col-md-3" id="final-target-container" style="display:none;">
        <label class="form-label fw-bold">Final Session Target</label>
        <input type="number" id="final-target" class="form-control" value="10" min="1">
    </div>

</div>

<hr>

<ul class="nav nav-tabs mb-3" id="session-tabs"></ul>
<div class="tab-content" id="session-content"></div>

</div>
</div>

<style>
.selected-row {
    background-color: #f2f2f2;
}
.selected-row select {
    font-weight: 600;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {

const formatSelect = document.getElementById('qualifying-format');
const eliminationCheckbox = document.getElementById('enable-elimination');
const finalTargetInput = document.getElementById('final-target');
const finalTargetContainer = document.getElementById('final-target-container');
const tabsContainer = document.getElementById('session-tabs');
const contentContainer = document.getElementById('session-content');
const savedSessions = @json($savedSessions);

const classGroups = @json($classGroups);
const totalParticipants = {{ $raceCars->count() }};
let hasSavedSessions = savedSessions.length > 0;
console.log(savedSessions);
if (hasSavedSessions) {

    formatSelect.value = savedSessions.length;

    eliminationCheckbox.checked =
        savedSessions[0].is_elimination ? true : false;

    if (savedSessions[0].is_elimination) {
        finalTargetContainer.style.display = 'block';
        finalTargetInput.value = savedSessions[0].final_target;
    }
}

function calculateEliminations(sessionCount, finalTarget) {

    if (!eliminationCheckbox.checked ||
        sessionCount <= 1 ||
        totalParticipants <= finalTarget) {

        return Array(sessionCount).fill(0);
    }

    const totalToEliminate = totalParticipants - finalTarget;
    const earlySessions = sessionCount - 1;

    const base = Math.floor(totalToEliminate / earlySessions);
    const remainder = totalToEliminate % earlySessions;

    const eliminations = [];

    for (let i = 0; i < sessionCount; i++) {
        if (i === sessionCount - 1) {
            eliminations.push(0);
        } else {
            eliminations.push(base + (i < remainder ? 1 : 0));
        }
    }

    return eliminations;
}

function renderSessions() {

    const sessionCount = parseInt(formatSelect.value);
    const finalTarget = parseInt(finalTargetInput.value) || 0;

    document.getElementById('format-hidden').value = sessionCount;
    document.getElementById('elimination-hidden').value =
        eliminationCheckbox.checked ? 1 : 0;
    document.getElementById('target-hidden').value = finalTarget;

    const eliminations = calculateEliminations(sessionCount, finalTarget);

    tabsContainer.innerHTML = '';
    contentContainer.innerHTML = '';
    let resultIndex = 0;
    for (let i = 0; i < sessionCount; i++) {

        const sessionName = sessionCount === 1 ? 'Qualifying' : `Q${i+1}`;
        const activeClass = i === 0 ? 'active' : '';

        tabsContainer.innerHTML += `
            <li class="nav-item">
                <button class="nav-link ${activeClass}"
                        data-bs-toggle="tab"
                        data-bs-target="#session-${i}">
                    ${sessionName}
                </button>
            </li>
        `;

        let sessionHTML = `
            <div class="tab-pane fade show ${activeClass}" id="session-${i}">
        `;

        if (eliminationCheckbox.checked) {
            sessionHTML += `
                <div class="alert alert-info">
                    Eliminated this session: <strong>${eliminations[i]}</strong>
                </div>
            `;
        }

        classGroups.forEach(group => {

            sessionHTML += `
                <h6 class="fw-bold text-uppercase mb-3 border-bottom pb-2">
                    ${group.name}
                </h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm text-center align-middle">
                        <thead class="table-light">
                            <tr>
                                <th style="width:80px;">Position</th>
                                <th>Car</th>
                                <th style="width:160px;">Best Lap</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            for (let pos = 1; pos <= group.cars.length; pos++) {

                sessionHTML += `
                    <tr>
                        <td class="fw-bold">
                            ${pos}
                            <input type="hidden"
                                name="qualifying[sessions][${i}][results][${resultIndex}][position]"
                                value="${pos}">
                        </td>
                        <td>
                            <select name="qualifying[sessions][${i}][results][${resultIndex}][entry_car_id]"      
                                class="form-select form-select-sm qualifying-select">
                                <option value="">-- Select Car --</option>
                                ${group.cars.map(car => {

                                    const entrant = car.entry_class?.season_entry?.entrant?.name ?? '';
                                    const livery = car.livery_name ?? '';
                                    const carModel = car.car_model?.name ?? '';

                                    const displayName = livery ? livery : entrant;
                                    let label = `#${car.car_number} ${displayName}`;
                                    if (carModel) label += ` – ${carModel}`;

                                    return `<option value="${car.id}">${label}</option>`;
                                }).join('')}
                            </select>
                        </td>
                        <td>
                            <input type="text"
                                name="qualifying[sessions][${i}][results][${resultIndex}][best_lap]"
                                class="form-control form-control-sm lap-time-input"
                                placeholder="m:ss:ms"
                                maxlength="8">
                        </td>
                    </tr>
                `;
                resultIndex++;
            }

            sessionHTML += `</tbody></table></div>`;
        });

        sessionHTML += `</div>`;
        contentContainer.innerHTML += sessionHTML;
    }

    attachLapFormatting();
    attachDuplicateProtection();
    if (hasSavedSessions) {
        populateSavedData();
    }
}

function attachDuplicateProtection() {
    document.querySelectorAll('.tab-pane').forEach(sessionPane => {
        sessionPane.querySelectorAll('table').forEach(table => {
            const selects = table.querySelectorAll('.qualifying-select');

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

                        const row = s.closest('tr');
                        if (s.value !== '') {
                            row.classList.add('selected-row');
                        } else {
                            row.classList.remove('selected-row');
                        }
                    });
                });
            });
        });
    });
}

function attachLapFormatting() {
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
}

function populateSavedData() {

    savedSessions.forEach((session, sessionIndex) => {

        session.results.forEach(result => {

            if (!result.entry_car_id) return;

            // Find all selects in this session
            const selects = document.querySelectorAll(
                `select[name^="qualifying[sessions][${sessionIndex}]"]`
            );

            selects.forEach(select => {

                const option = select.querySelector(
                    `option[value="${result.entry_car_id}"]`
                );

                if (!option) return;

                const row = select.closest('tr');
                const positionInput = row.querySelector('input[type="hidden"]');

                if (!positionInput) return;

                if (parseInt(positionInput.value) === parseInt(result.position)) {

                    select.value = result.entry_car_id;
                    select.dispatchEvent(new Event('change'));

                    const lapInput = row.querySelector('.lap-time-input');

                    if (lapInput && result.best_lap) {
                        lapInput.value = result.best_lap;
                    }
                }
            });

        });

    });

}

eliminationCheckbox.addEventListener('change', function () {
    finalTargetContainer.style.display = this.checked ? 'block' : 'none';
    renderSessions();
});

formatSelect.addEventListener('change', renderSessions);
finalTargetInput.addEventListener('input', renderSessions);

renderSessions();

});
</script>