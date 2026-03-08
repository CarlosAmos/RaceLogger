@extends('layouts.app')

@section('content')
<div class="container">
    <form method="POST"
          action="{{ route(
            'entry-cars.store',
            [$world, $season, $seasonEntry]
          ) }}">
        @csrf

        
        @if($entryClasses->count() > 1) 
        <div class="mb-3">
            <label class="form-label">Class</label>
            <select name="entry_class_id" class="form-select" required>
                @foreach($entryClasses as $class)
                    <option value="{{ $class->id }}">
                        {{ $class->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @else
        <input id="entry_class_id" name="entry_class_id" value="{{$entryClasses[0]->id}}" hidden>{{$entryClasses[0]->name}}</input>
        @endif
        <input id="season_entry_id" name="season_entry_id" value="{{$seasonEntry->id}}" hidden>{{$seasonEntry->name}}</input>

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