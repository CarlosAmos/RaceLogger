@extends('layouts.app')

@section('content')
<div class="container">

    <div class="mb-3">
        <a href="{{ route('worlds.constructors.car-models.index', [$world, $constructor]) }}"
           class="btn btn-secondary btn-sm">
            ← Back to Car Models
        </a>
    </div>

    <h2>Add Car Model – {{ $constructor->name }}</h2>

    <form method="POST"
          action="{{ route('worlds.constructors.car-models.store', [$world, $constructor]) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Model Name</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Year</label>
            <input type="number"
                   name="year"
                   class="form-control"
                   placeholder="2024" value="2023">
        </div>

        <div class="mb-3">
            <label class="form-label">Engine</label>
            <select name="engine_id" class="form-select">
                <option value="">-- No Engine --</option>

                @foreach($engines as $engine)
                    <option value="{{ $engine->id }}">
                        {{ $engine->name }}
                        @if($engine->configuration)
                            ({{ $engine->capacity.'L' ?? '' }} {{ $engine->configuration }})
                            {{ $engine->hybrid ? '-- Hybrid' : '' }}
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <button class="btn btn-primary">
            Create Car Model
        </button>
    </form>

</div>
@endsection
