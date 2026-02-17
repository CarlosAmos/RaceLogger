@extends('layouts.app')

@section('content')

<h1>Create Season</h1>

<form method="POST" action="{{ route('seasons.store') }}">
    @csrf

    <div>
        <label>Select Series</label><br>
        <select name="series_id" required>
            @foreach($series as $s)
                <option value="{{ $s->id }}"
                    {{ (isset($seriesId) && $seriesId == $s->id) ? 'selected' : '' }}>
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


    <br>

    <button type="submit">Create Season</button>
</form>

@endsection
