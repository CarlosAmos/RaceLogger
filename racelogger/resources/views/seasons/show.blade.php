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
                            <span class="badge bg-primary me-1 mb-1">
                                {{ $class->raceClass->name }}
                            </span>
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



</div>
@endsection
