@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Add Team to {{ $season->name }}</h1>

    <form method="POST"
          action="{{ route('worlds.seasons.season-entries.store', [$world, $season]) }}">
        @csrf

        <div class="mb-3">
            <label>Entrant</label>
            <select name="entrant_id" class="form-control" required>
                <option value="">Select Entrant</option>
                @foreach($entrants as $entrant)
                    <option value="{{ $entrant->id }}">
                        {{ $entrant->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Constructor</label>
            <select name="constructor_id" class="form-control" required>
                <option value="">Select Constructor</option>
                @foreach($constructors as $constructor)
                    <option value="{{ $constructor->id }}">
                        {{ $constructor->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Display Name (Optional)</label>
            <input type="text"
                   name="display_name"
                   class="form-control">
        </div>

        <button type="submit"
                class="btn btn-success">
            Add Team
        </button>

    </form>

</div>
@endsection
