@extends('layouts.app')

@section('content')
<div class="container">

    <h2>Add Entry Car – {{ $entryClass->name }}</h2>

    <form method="POST"
          action="{{ route(
            'worlds.seasons.season-entries.entry-classes.entry-cars.store',
            [$world, $season, $seasonEntry, $entryClass]
          ) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Car Number</label>
            <input type="text" name="car_number"
                   class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Car Model</label>
            <select name="car_model_id" class="form-select" required>
                @foreach($carModels as $model)
                    <option value="{{ $model->id }}">
                        {{ $model->name }}
                        @if($model->engine)
                            ({{ $model->engine->name }})
                        @endif
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Livery Name</label>
            <input type="text" name="livery_name"
                   class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Chassis Code</label>
            <input type="text" name="chassis_code"
                   class="form-control">
        </div>

        <button class="btn btn-primary">
            Create Entry Car
        </button>
    </form>

</div>
@endsection