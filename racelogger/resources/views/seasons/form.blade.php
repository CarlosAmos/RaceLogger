@extends('layouts.app')

@section('content')

<style>
.page-card {
    background: #ffffff;
    padding: 25px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    margin-bottom: 20px;
}

.tab-nav {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
}

.tab-nav button {
    padding: 8px 14px;
    border: none;
    background: #e9ecef;
    cursor: pointer;
    border-radius: 6px;
    font-weight: 500;
}

.tab-nav button.active {
    background: #007bff;
    color: white;
}

.tab-section {
    background: #fafafa;
    padding: 20px;
    border-radius: 6px;
}

.circuit-card {
    border: 1px solid #ddd;
    padding: 15px;
    margin-bottom: 12px;
    border-radius: 6px;
    background: white;
}

.small-btn {
    padding: 4px 8px;
    font-size: 12px;
    border-radius: 4px;
}

.points-preview {
    background: #ffffff;
    padding: 15px;
    border-radius: 6px;
    border: 1px solid #ddd;
    margin-top: 15px;
}

.team_entry {
    background: #ebebeb;
    border-radius: 10px;
    margin:7px 0;
    padding:5px 15px;
}

.entry-car {
    border: 2px solid white;
    padding: 4px;
    margin:4px;
    border-radius: 20px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 1) !important;
    width:185px;
}

.entry-car-no {
    border-radius: 80px;
    color: black;
    font-size: 50px;
    padding: 13px 13px;
    text-align: center;
    width: 100px;
    height: 100px;
}

.entry-driver-list {
    display: flex;
    flex-direction: column;
    align-items: center;
}

.entry-driver {
    padding: 2px 8px;
    background: #ffffff;
    margin: 2px 0;
    border-radius: 20px;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.05) !important;
    text-align: center;
    font-size: 14px;
}

.entry-class {
    font-size:1rem; 
    text-align:center; 
    font-style:italic; 
    margin-bottom: -5px;
    padding: 2px 8px;
    color:white;
    border-radius: 20px;
    margin-bottom:4px;
}

</style>


<div class="page-card">

<h1>{{ $mode === 'create' ? 'Create Season' : 'Edit Season' }}</h1>

<form method="POST"
      action="{{ $mode === 'create'
            ? route('seasons.store')
            : route('seasons.update', $season) }}">

    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    <div class="tab-nav">
        <button type="button" onclick="showTab('circuits', this)">Circuits</button>
        <button type="button" onclick="showTab('classes', this)">Classes</button>
        <button type="button" onclick="showTab('teams', this)">Teams</button>
        <button type="button" onclick="showTab('points', this)">Points</button>
        <button type="button" onclick="showTab('basic', this)">Basic Info</button>        
    </div>


    {{-- CIRCUITS TAB --}}
    <div id="circuits" class="tab-section">
        <h3>Season Calendar</h3>
        <div id="selected-list"></div>
        <hr>
        <h4>Add Circuits</h4>
        <div style="display:flex; flex-wrap:wrap; gap:10px;">
            @foreach($layouts as $layout)
                <div onclick="addCircuit(
                    {{ $layout->id }},
                    '{{ addslashes($layout->track->name) }}',
                    '{{ addslashes($layout->name) }}',
                    '{{ addslashes($layout->track->city ?? '') }}',
                    '{{ addslashes(optional($layout->track->country)->name ?? '') }}'
                )"
                style="border:1px solid #ccc; padding:10px; cursor:pointer; width:200px; border-radius:6px;">
                    <strong>{{ $layout->track->name }}</strong><br>
                    <small>{{ $layout->name }}</small>
                </div>
            @endforeach
        </div>
    </div>

    {{-- CLASSES TAB --}}
    <div id="classes" class="tab-section" style="display:none;">
        <h3>Season Classes</h3>
        <div id="class-list"></div>
        <button type="button" onclick="addClass()" class="small-btn">+ Add Class</button>

    </div>

    {{-- TEAMS TAB --}}
    <div id="teams" class="tab-section" style="display:none;">
        <div id="team-class-list"></div>

        <?php
        print("<pre>");
        print_r($season->seasonEntries[0]);
        print("</pre>");
        ?>

    </div>

    {{-- POINTS TAB --}}
    <div id="points" class="tab-section" style="display:none;">
        <h3>Default Points System</h3>

        <div style="display:flex; gap:10px; align-items:center;">
            <select name="point_system_id" id="seasonPointSystem">
                <option value="">-- No Points System --</option>
                @foreach($pointSystems as $ps)
                    <option value="{{ $ps->id }}"
                        {{ optional($season)->point_system_id == $ps->id ? 'selected' : '' }}>
                        {{ $ps->name }}
                    </option>
                @endforeach
            </select>

            <a href="{{ route('point-systems.create', ['season_id' => optional($season)->id]) }}"
               class="small-btn"
               style="background:#28a745; color:white; text-decoration:none;">
                + Create
            </a>
        </div>

        <div id="pointSystemPreview" class="points-preview"></div>
    </div>

    {{-- ===================== --}}
    {{-- BASIC INFO --}}
    {{-- ===================== --}}
    <div id="basic" class="tab-section" style="display:none;">
        <label>Series</label><br>
        <select name="series_id" required>
            @foreach($series as $s)
                <option value="{{ $s->id }}"
                    {{ ($seriesId == $s->id) ? 'selected' : '' }}>
                    {{ $s->name }}
                </option>
            @endforeach
        </select>

        <br><br>

        <label>Season Year</label><br>
        <input type="number"
               name="year"
               value="{{ old('year', $defaultYear) }}"
               required>
    </div>

    <div id="circuit-inputs"></div>
    <div id="class-inputs"></div>

    <br><hr>

    <button type="submit"
            style="padding:10px 20px; background:#007bff; color:white; border:none; border-radius:6px;">
        {{ $mode === 'create' ? 'Create Season' : 'Update Season' }}
    </button>

</form>
</div>

<script>

function showTab(tabId, btn) {
    document.querySelectorAll('.tab-section')
        .forEach(tab => tab.style.display = 'none');

    document.querySelectorAll('.tab-nav button')
        .forEach(b => b.classList.remove('active'));

    document.getElementById(tabId).style.display = 'block';

    if (btn) btn.classList.add('active');
}
showTab('circuits', document.querySelector('.tab-nav button'));

const pointSystems = @json($pointSystems);

/* ======================
   POINT SYSTEM PREVIEW
====================== */
document.getElementById('seasonPointSystem')
.addEventListener('change', function() {

    const system = pointSystems.find(ps => ps.id == this.value);
    const preview = document.getElementById('pointSystemPreview');
    preview.innerHTML = '';

    if (!system) return;

    let html = '<strong>Race Points</strong><ul>';

    system.rules
        .filter(r => r.type === 'race')
        .sort((a,b) => a.position - b.position)
        .forEach(r => {
            html += `<li>P${r.position} → ${r.points} pts</li>`;
        });

    html += '</ul>';

    preview.innerHTML = html;
});

/* ======================
   CIRCUIT PRELOAD
====================== */

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
        raceDate: "{{ $race->race_date }}",
        pointSystemId: "{{ $race->point_system_id ?? '' }}"
    }@if(!$loop->last),@endif
    @endforeach
];
@endif

function renderCircuits() {
    const list = document.getElementById('selected-list');
    const inputs = document.getElementById('circuit-inputs');
    list.innerHTML = '';
    inputs.innerHTML = '';

    selectedCircuits.forEach((c,i) => {

        const div = document.createElement('div');
        div.className = "circuit-card";

        div.innerHTML = `
            <strong>Round ${i+1}</strong><br><br>

            <input type="text" value="${c.gpName}"
                placeholder="Grand Prix Name"
                oninput="selectedCircuits[${i}].gpName=this.value; renderCircuits();">

            <input type="text" maxlength="3"
                value="${c.raceCode}"
                placeholder="CODE"
                oninput="selectedCircuits[${i}].raceCode=this.value.toUpperCase(); renderCircuits();">

            <input type="date"
                value="${c.raceDate || ''}"
                onchange="selectedCircuits[${i}].raceDate=this.value; renderCircuits();">

            <br><br>

            ${c.trackName} (${c.layoutName}) - ${c.city}, ${c.country}

            <br><br>

            <label>Override Points</label>
            <select onchange="selectedCircuits[${i}].pointSystemId=this.value; renderCircuits();">
                <option value="">Season Default</option>
                ${pointSystems.map(ps =>
                    `<option value="${ps.id}"
                     ${c.pointSystemId == ps.id ? 'selected' : ''}>
                     ${ps.name}
                     </option>`
                ).join('')}
            </select>

            <button type="button"
                onclick="selectedCircuits.splice(${i},1); renderCircuits();"
                class="small-btn">Remove</button>
        `;

        list.appendChild(div);

        inputs.innerHTML += `
            <input type="hidden" name="circuits[${i}][layout_id]" value="${c.id}">
            <input type="hidden" name="circuits[${i}][gp_name]" value="${c.gpName}">
            <input type="hidden" name="circuits[${i}][race_code]" value="${c.raceCode}">
            <input type="hidden" name="circuits[${i}][race_date]" value="${c.raceDate || ''}">
            <input type="hidden" name="circuits[${i}][point_system_id]" value="${c.pointSystemId}">
        `;
    });
}
renderCircuits();

/* ======================
   CLASS PRELOAD
====================== */

let seasonClasses = [];

@if(isset($season) && $season->classes && $season->classes->count())
seasonClasses = [
    @foreach($season->classes as $class)
        

        { id: "{{$class->id}}", name: "{{ addslashes($class->name) }}" }@if(!$loop->last),@endif
    @endforeach
];
@endif


let seasonEntries = [];
let i = 0;

@if(isset($season) && $season->seasonEntries && $season->seasonEntries->count())

    @foreach($season->seasonEntries as $seasonEntry)
        
        @if(!$loop->last)@endif

        @if($seasonEntry->entrant && $seasonEntry->entrant)        
            @foreach($seasonEntry->entryClasses as $entryClass)
                if (!seasonEntries[{{$entryClass->raceClass->id}}]) {
                    seasonEntries[{{$entryClass->raceClass->id}}] = {};
                }

                @foreach($entryClass->entryCars as $entryCar)

                    if (!seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}] ) {
                        seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}] = {};
                    }

                    if (!seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}]["entryCar"] ) {
                        seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}]["entryCar"] = {};
                        seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}]["entrantName"] = "{{ $seasonEntry->entrant->name}}";
                    }

                    console.log("{{$seasonEntry->entrant->name}} - {{$entryClass->raceClass->id}} -> {{$entryCar->id}}");  
                    seasonEntries[{{$entryClass->raceClass->id}}][{{$entryCar->entry_class_id}}]["entryCar"][{{$entryCar->id}}] = {
                        entryId: "{{ addslashes($seasonEntry->entrant_id) }}",
                        entrantName:  "{{ $seasonEntry->entrant->name}}",
                        carNo: "{{$entryCar->car_number}}",
                        entryClassId: "{{$entryCar->entry_class_id}}",
                    };
                @endforeach  
            @endforeach
        @endif
    i++;
    @endforeach
    console.log("Entries",seasonEntries);
@endif

function renderClasses() {

    const list = document.getElementById('class-list');
    const teamClassList = document.getElementById('team-class-list');
    const inputs = document.getElementById('class-inputs');

    list.innerHTML = '';
    inputs.innerHTML = '';

    seasonClasses.forEach((c,i) => {

        const div = document.createElement('div');
        div.className = "class-row";

        div.innerHTML = `
            <input type="text"
                value="${c.name}"
                oninput="seasonClasses[${i}].name=this.value; renderClasses();">
            <button type="button"
                onclick="seasonClasses.splice(${i},1); renderClasses();"
                class="small-btn">✕</button>
        `;

        list.appendChild(div);
        inputs.innerHTML += `<input type="hidden" name="classes[${i}]" value="${c.name}">`;
    });



    const teamDiv = document.createElement('div'); 
    teamDiv.className = "class-row";
    let teams = `
    <div class="row">
        @foreach($season->seasonEntries as $entry)
        <div class="shadow-sm mb-3" style="border-radius:10px;">
            <div class="d-flex justify-content-between" style="border-bottom:2px solid #d3d3d3;">
                <div class="d-flex">
                    <h5 class="mb-1" style="font-weight:600; padding: 2px 10px;">{{$entry->display_name ?? $entry->entrant->name}}</h5>
                    @if($entry->display_name)
                        <small class="text-muted" style="font-style:italic;">
                            {{ $entry->entrant->name }}
                        </small>
                    @endif
                    <div>
                        <div style="color: green; border-radius: 20px; padding: 0 7px; border: 1px solid green; user-select:none; cursor:pointer; ">+</div>                                           
                    </div>
                </div>
                <div>
                    <h5 class="shadow-sm mb-1" style="font-weight:100; padding:2px 10px; margin-top:1px; border-radius: 20px;"> {{ $entry->constructor->name }}</h5>
                </div>
            </div>
            <div class="mt-2 d-flex flex-row">
                @forelse($entry->entryClasses as $class)
                    @forelse($class->entryCars as $entryCar)
                        @php    
                        $raceNumberColour = "black";
                        $entryBorder = " box-shadow: 0 0 0.25rem rgba(0, 0, 0, 1) !important;";
                        if(strtolower($class->raceClass->name) === "hypercar") {
                            $raceNumberColour = "red";
                            $entryBorder = " box-shadow: 0 0 0.25rem red !important;";
                        }
                        else if(strtolower($class->raceClass->name) === "lmgte am") {
                            $raceNumberColour = "#ff9b00";
                            $entryBorder = " box-shadow: 0 0 0.25rem #ff9b00 !important;";
                        }
                        else if(strtolower($class->raceClass->name) === "lmp2") {
                            $raceNumberColour = "blue";
                            $entryBorder = " box-shadow: 0 0 0.25rem blue !important;";
                        }
                        @endphp

                        <div class="entry-car me-3" style="display: flex; flex-direction: column; align-items: center; {{$entryBorder}}">
                            <h5 class="entry-class" style="background:{{$raceNumberColour}};">{{ $class->raceClass->name }}</h5>
                            <strong style="margin-bottom: -5px; text-align:center; text-wrap:auto;">{{$entryCar->carModel->name}}</strong>
                            <div style="font-style:italic; margin-bottom: 4px;">{{$entryCar->carModel->year}}</div>
                            <div class="entry-car-no" style="">{{$entryCar->car_number}}</div>
                            <div class="entry-driver-list mt-2">
                                @forelse($entryCar->drivers as $driver)
                                    <div class="entry-driver">{{$driver->first_name}} {{$driver->last_name}}</div>
                                    @empty
                                    <span class="text-muted small">
                                        No Drivers
                                    </span>
                                @endforelse
                            </div>
                        </div>
                    @empty
                    <span class="text-muted small">
                        No Cars
                    </span>
                @endforelse

                @empty
                <span class="text-muted small">
                    No classes
                </span>
                @endforelse
            </div>
        </div>            
        @endforeach 
    </div>
    `;

    teamDiv.innerHTML = teams;
    teamClassList.appendChild(teamDiv);
}
renderClasses();

function addClass(){
    seasonClasses.push({name:''});
    renderClasses();
}

function addCircuit(id, trackName, layoutName, city, country){
    if (selectedCircuits.find(c => c.id === id)) return;

    selectedCircuits.push({
        id, trackName, layoutName, city, country,
        gpName:'', raceCode:'', raceDate:'', pointSystemId:''
    });

    renderCircuits();
}

</script>

@endsection