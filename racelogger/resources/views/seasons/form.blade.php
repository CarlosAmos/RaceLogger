@extends('layouts.app')

@section('content')


@if ($errors->any())
    <div style="background:#f8d7da; padding:10px; margin-bottom:15px;">
        <ul style="margin:0;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif


<h1>{{ $mode === 'create' ? 'Create Season' : 'Edit Season' }}</h1>
<form method="POST"
      action="{{ $mode === 'create' ? route('seasons.store') : route('seasons.update', $season, $worlds) }}">

    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    {{-- TAB NAV --}}
    <div style="margin-bottom: 20px;">
        <button type="button" onclick="showTab('circuits')">Circuits</button>

        @php
            $selectedSeries = $series->firstWhere('id', $seriesId);
        @endphp

        @if($selectedSeries && $selectedSeries->is_multiclass)
            <button type="button" onclick="showTab('classes')">Classes</button>
        @endif

        <button type="button" onclick="showTab('basic')">Basic Info</button>
    </div>

    {{-- ===================== --}}
    {{-- CIRCUITS TAB --}}
    {{-- ===================== --}}
    <div id="circuits" class="tab-section">

        <h3>Select Circuits</h3>

        {{-- Selected Circuits List --}}
        <div style="margin-bottom:20px; padding:10px; background:#f0f0f0;">
            <strong>Selected Circuits (Drag to Reorder)</strong>
            <ul id="selected-list"
                style="list-style:none; padding:0; margin-top:10px;"></ul>
        </div>

        {{-- Available Circuits --}}
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @foreach($layouts as $layout)
                <div onclick="addCircuit(
                    {{ $layout->id }},
                    '{{ addslashes($layout->track->name) }}',
                    '{{ addslashes($layout->name) }}',
                    '{{ addslashes($layout->track->city ?? '') }}',
                    '{{ addslashes($layout->track->country ? $layout->track->country->name : '') }}'
                )"
                     style="border:1px solid #ccc; padding:10px; cursor:pointer; width:200px;">
                    <strong>{{ $layout->track->name }}</strong><br>
                    Layout: {{ $layout->name }}<br>
                    Length: {{ $layout->length_km ?? 'N/A' }} km
                </div>
            @endforeach
        </div>

    </div>

    {{-- ===================== --}}
    {{-- CLASSES TAB --}}
    {{-- ===================== --}}
    <div id="classes" class="tab-section" style="display:none;">

        <h3>Season Classes</h3>

        <div id="class-list" style="margin-bottom:15px;">
            {{-- JS will render classes here --}}
        </div>

        <div style="margin-top:10px;">
            <button type="button" onclick="addClass()">+ Add Class</button>
        </div>

    </div>

    {{-- Hidden inputs --}}
    <div id="class-inputs"></div>

    {{-- ===================== --}}
    {{-- BASIC INFO TAB --}}
    {{-- ===================== --}}
    <div id="basic" class="tab-section" style="display:none;">

        <div>
            <label>Select Series</label><br>
            <select name="series_id" required>
                @foreach($series as $s)
                    <option value="{{ $s->id }}"
                        {{ ($seriesId == $s->id) ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <br>

        <div>
            <label>Season Year</label><br>
            <input type="number"
                   name="year"
                   value="{{ old('year', $defaultYear) }}"
                   required>
        </div>

    </div>

    {{-- Hidden circuit inputs --}}
    <div id="circuit-inputs"></div>

    {{-- Hidden class inputs --}}
    <div id="class-inputs"></div>

    <br>
    <hr>

    <button type="submit">
        {{ $mode === 'create' ? 'Create Season' : 'Update Season' }}
    </button>

</form>

{{-- ===================== --}}
{{-- JAVASCRIPT --}}
{{-- ===================== --}}
<script>

    function showTab(tabId) {
        document.querySelectorAll('.tab-section')
            .forEach(tab => tab.style.display = 'none');

        document.getElementById(tabId).style.display = 'block';
    }

    let selectedCircuits = [];

    @if(isset($calendarRaces) && $calendarRaces->count())
        selectedCircuits = [
            @foreach($calendarRaces as $race)
                {
                    id: {{ $race->track_layout_id }},
                    trackName: "{{ addslashes($race->layout->track->name) }}",
                    layoutName: "{{ addslashes($race->layout->name) }}",
                    city: "{{ addslashes($race->layout->track->city ?? '') }}",
                    country: "{{ addslashes(optional($race->layout->track->country)->name ?? '') }}",
                    gpName: "{{ addslashes($race->gp_name) }}",
                    raceCode: "{{ $race->race_code }}",
                    raceDate: "{{ $race->race_date }}"
                }@if(!$loop->last),@endif
            @endforeach
        ];
    @endif

    function addCircuit(id, trackName, layoutName, city, country) {

        if (selectedCircuits.find(c => c.id === id)) return;

        selectedCircuits.push({
            id,
            trackName,
            layoutName,
            city,
            country,
            gpName: '',
            raceCode: '',
            raceDate: ''
        });

        renderCircuits();
    }

    function renderCircuits() {

    const list = document.getElementById('selected-list');
    const inputContainer = document.getElementById('circuit-inputs');

    list.innerHTML = '';
    inputContainer.innerHTML = '';

        selectedCircuits.forEach((circuit, index) => {

            const li = document.createElement('li');
            li.draggable = true;
            li.dataset.index = index;

            li.style.display = "flex";
            li.style.alignItems = "center";
            li.style.gap = "10px";
            li.style.padding = "8px";
            li.style.border = "1px solid #ccc";
            li.style.marginBottom = "6px";
            li.style.background = "#fff";
            li.style.cursor = "move";

            li.innerHTML = `
                <strong style="width:50px;">R${index + 1}</strong>

                <input type="text"
                    placeholder="Grand Prix Name"
                    value="${circuit.gpName}"
                    style="flex:2;"
                    oninput="updateGPName(${index}, this.value)">

                <input type="text"
                    maxlength="3"
                    placeholder="CODE"
                    value="${circuit.raceCode}"
                    style="width:70px; text-transform:uppercase;"
                    oninput="updateRaceCode(${index}, this.value.toUpperCase())">

                <input type="date"
                    value="${circuit.raceDate || ''}"
                    style="width:150px;"
                    onchange="updateRaceDate(${index}, this.value)">

                <div style="flex:3;">
                    <strong>${circuit.trackName}</strong>
                    <span style="color:#777;">
                        (${circuit.layoutName})
                    </span>
                </div>

                <div style="flex:2; color:#555;">
                    ${circuit.city}, ${circuit.country}
                </div>

                <button type="button"
                        style="background:#d9534f; color:white; border:none; padding:4px 8px;"
                        onclick="removeCircuit(${index})">
                    ✕
                </button>
            `;

            li.addEventListener('dragstart', dragStart);
            li.addEventListener('dragover', dragOver);
            li.addEventListener('drop', drop);

            list.appendChild(li);

            updateHiddenInputs();
        });
    }

    function updateHiddenInputs() {
        const inputContainer = document.getElementById('circuit-inputs');
        inputContainer.innerHTML = '';

        selectedCircuits.forEach((circuit, index) => {

            inputContainer.innerHTML += `
                <input type="hidden" name="circuits[${index}][layout_id]" value="${circuit.id}">
                <input type="hidden" name="circuits[${index}][gp_name]" value="${circuit.gpName}">
                <input type="hidden" name="circuits[${index}][race_code]" value="${circuit.raceCode}">
                <input type="hidden" name="circuits[${index}][race_date]" value="${circuit.raceDate || ''}">
            `;
        });
    }

    function updateRaceDate(index, value) {
        selectedCircuits[index].raceDate = value;
        updateHiddenInputs();
    }

    function removeCircuit(index) {
        selectedCircuits.splice(index, 1);
        renderCircuits();
    }


    function updateGPName(index, value) {
        selectedCircuits[index].gpName = value;
        updateHiddenInputs();
    }

    function updateRaceCode(index, value) {
        selectedCircuits[index].raceCode = value;
        updateHiddenInputs();
    }

    let draggedIndex = null;

    function dragStart(e) {
        draggedIndex = e.target.dataset.index;
    }

    function dragOver(e) {
        e.preventDefault();
    }

    function drop(e) {
        const targetIndex = e.target.closest('li').dataset.index;

        const temp = selectedCircuits[draggedIndex];
        selectedCircuits.splice(draggedIndex, 1);
        selectedCircuits.splice(targetIndex, 0, temp);

        renderCircuits();
    }

    renderCircuits();
    showTab('circuits');

    // =========================
// CLASS LOGIC
// =========================

let seasonClasses = [];

// 🔥 Load existing classes (edit mode)
@if(isset($season) && $season->classes && $season->classes->count())
    seasonClasses = [
        @foreach($season->classes as $class)
            {
                name: "{{ addslashes($class->name) }}"
            }@if(!$loop->last),@endif
        @endforeach
    ];
@endif

function addClass() {
    seasonClasses.push({
        name: ''
    });

    renderClasses();
}

function removeClass(index) {
    seasonClasses.splice(index, 1);
    renderClasses();
}

function updateClassName(index, value) {
    seasonClasses[index].name = value;
    updateClassHiddenInputs();
}

function renderClasses() {

    const list = document.getElementById('class-list');
    list.innerHTML = '';

    if (seasonClasses.length === 0) {
        list.innerHTML = `
            <div style="color:#777; font-style:italic;">
                No classes added. If left empty, a default "Overall" class will be created.
            </div>
        `;
    }

    seasonClasses.forEach((cls, index) => {

        const row = document.createElement('div');

        row.style.display = "flex";
        row.style.alignItems = "center";
        row.style.gap = "10px";
        row.style.marginBottom = "8px";
        row.style.padding = "6px";
        row.style.border = "1px solid #ddd";
        row.style.background = "#fafafa";

        row.innerHTML = `
            <strong style="width:40px;">${index + 1}</strong>

            <input type="text"
                   placeholder="Class Name (e.g. Hypercar)"
                   value="${cls.name}"
                   style="flex:1;"
                   oninput="updateClassName(${index}, this.value)">

            <button type="button"
                    style="background:#d9534f; color:white; border:none; padding:4px 8px;"
                    onclick="removeClass(${index})">
                ✕
            </button>
        `;

        list.appendChild(row);
    });

    updateClassHiddenInputs();
}

function updateClassHiddenInputs() {

    const container = document.getElementById('class-inputs');
    container.innerHTML = '';

    seasonClasses.forEach((cls, index) => {

        container.innerHTML += `
            <input type="hidden" name="classes[${index}]" value="${cls.name}">
        `;
    });
}

// Initial render
renderClasses();

</script>


@endsection
