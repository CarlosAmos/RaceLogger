@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Create Team</h1>

    <form method="POST" 
          action="{{ route('worlds.constructors.store', $world) }}">
        @csrf

        <div class="mb-3">
            <label>Name</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label>Country</label>
            <select name="country_id" class="form-control">
                <option value="">Select Country</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label>Color (Hex)</label>
            <input type="text" name="color" class="form-control" placeholder="#FF0000">
        </div>
        <input type="hidden" name="world_id" value="{{ $world->id }}">
        <button type="submit" class="btn btn-success">
            Save Team
        </button>

    </form>

</div>
@endsection
