@extends('layouts.app')

@section('content')
<div class="container">

    @php
    $participantsExist = $race->entryCars()->exists();
    @endphp

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $race->season->name }}</h2>
            <p class="mb-0 text-muted">
                Manage Weekend — {{ $race->race_name ?? $race->race_code }}
            </p>
        </div>

        <a href="{{ route('seasons.show', [$race->season, 'tab' => 'results', 'has_sprint' => $hasSprint]) }}"
            class="btn btn-outline-secondary">
            Back to Season
        </a>
    </div>

    <form method="POST"
        action="{{ route('races.weekend.update', [$race, 'has_sprint' => $hasSprint ]) }}">

        @csrf
        <input type="hidden" name="submitted_tab" id="submitted-tab">

        {{-- Tabs --}}
        <ul class="nav nav-tabs mb-4">

            <li class="nav-item">
                <button class="nav-link {{ $defaultTab === 'participants' ? 'active' : '' }}"
                    data-bs-toggle="tab"
                    data-bs-target="#participants">
                    Participants
                </button>
            </li>

            <li class="nav-item">
                <button class="nav-link {{ $defaultTab === 'qualifying' ? 'active' : '' }} {{ !$participantsExist ? 'disabled' : '' }}"
                    data-bs-toggle="{{ $participantsExist ? 'tab' : '' }}"
                    data-bs-target="{{ $participantsExist ? '#qualifying' : '' }}">
                    Qualifying
                </button>
            </li>
            @if($hasSprint == 1)
            <li class="nav-item">
                <button class="nav-link {{ $defaultTab === 'sprint_race' ? 'active' : '' }} {{ !$participantsExist ? 'disabled' : '' }}"
                    data-bs-toggle="{{ $participantsExist ? 'tab' : '' }}"
                    data-bs-target="{{ $participantsExist ? '#sprint_race' : '' }}">
                    Sprint Race
                </button>
            </li>
            @endif
            <li class="nav-item">
                <button class="nav-link {{ $defaultTab === 'race' ? 'active' : '' }} {{ !$participantsExist ? 'disabled' : '' }}"
                    data-bs-toggle="{{ $participantsExist ? 'tab' : '' }}"
                    data-bs-target="{{ $participantsExist ? '#race' : '' }}">
                    Race Results
                </button>
            </li>

        </ul>

        {{-- Tab Content --}}
        <div class="tab-content">

            <div class="tab-pane fade {{ $defaultTab === 'participants' ? 'show active' : '' }}"
                id="participants">

                @include('races.weekend.partials.participants')

            </div>

            <div class="tab-pane fade {{ $defaultTab === 'qualifying' ? 'show active' : '' }}"
                id="qualifying">

                @if(!$participantsExist)
                <div class="alert alert-warning">
                    Please save participants first.
                </div>
                @else
                @include('races.weekend.partials.qualifying')
                @endif

            </div>

                <div class="tab-pane fade {{ $defaultTab === 'sprint_race' ? 'show active' : '' }}"
                id="sprint_race">

                @if(!$participantsExist)
                <div class="alert alert-warning">
                    Please save participants first.
                </div>
                @else
                @include('races.weekend.partials.sprint-race-results')
                @endif

            </div>

            <div class="tab-pane fade {{ $defaultTab === 'race' ? 'show active' : '' }}"
                id="race">

                @if(!$participantsExist)
                <div class="alert alert-warning">
                    Please save participants first.
                </div>
                @else
                @include('races.weekend.partials.race-results')
                @endif

            </div>

        </div>

        <div class="mt-4 d-flex justify-content-between">

            <button type="submit"
                    name="action"
                    value="save"
                    class="btn btn-primary btn-lg">
                Save Weekend
            </button>

            <button type="submit"
                    name="action"
                    value="complete"
                    class="btn btn-success btn-lg">
                Complete Weekend
            </button>

        </div>

    </form>

</div>

<script>
    document.querySelector('form').addEventListener('submit', function() {

        const activeTab = document.querySelector('.nav-link.active');
        if (!activeTab) return;

        const target = activeTab.getAttribute('data-bs-target');

        if (target === '#participants') {
            document.getElementById('submitted-tab').value = 'participants';
        }

        if (target === '#qualifying') {
            document.getElementById('submitted-tab').value = 'qualifying';
        }

        if (target === '#sprint_race') {
            document.getElementById('submitted-tab').value = 'sprint_race';
        }

        if (target === '#race') {
            document.getElementById('submitted-tab').value = 'race';
        }
    });
</script>
@endsection