@extends('layouts.app')

@section('content')
<div class="container">

    <h1>Create Entrant</h1>

    <form method="POST"
          action="{{ route('worlds.entrants.store', $world) }}">
        @csrf

        <div class="mb-3">
            <label class="form-label">Entrant Name</label>
            <input type="text"
                   name="name"
                   class="form-control"
                   value="{{ old('name') }}"
                   required>
        </div>

        <div class="mb-3">
            <label class="form-label">Country</label>
            <select name="country_id"
                    class="form-control">
                <option value="">Select Country</option>
                @foreach($countries as $country)
                    <option value="{{ $country->id }}">
                        {{ $country->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <button type="submit"
                class="btn btn-success">
            Create Entrant
        </button>

    </form>

</div>
@endsection
