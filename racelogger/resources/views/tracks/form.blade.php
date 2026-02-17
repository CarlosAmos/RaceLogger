@extends('layouts.app')

@section('content')

<h1>{{ $mode === 'create' ? 'Create Circuit' : 'Edit Circuit' }}</h1>

<form method="POST"
    action="{{ $mode === 'create' ? route('tracks.store') : route('tracks.update', $track) }}">

    @csrf
    @if($mode === 'edit')
    @method('PUT')
    @endif

    <div>
        <label>Circuit Name</label><br>
        <input type="text"
            name="name"
            value="{{ old('name', $track->name) }}"
            required>
    </div>

    <br>


    <div>
    <label>City</label><br>
    <input type="text"
           name="city"
           value="{{ old('city', $track->city) }}">
</div>

<br>
    <div>
    <label>Country</label><br>
    <select name="country_id">
        <option value="">-- Select Country --</option>
        @foreach($countries as $country)
            <option value="{{ $country->id }}"
                {{ old('country_id', $track->country_id) == $country->id ? 'selected' : '' }}>
                {{ $country->name }}
            </option>
        @endforeach
    </select>
</div>

<br>


    <br>

    <button type="submit">
        {{ $mode === 'create' ? 'Create Circuit' : 'Update Circuit' }}
    </button>
</form>

@if($mode === 'edit')

<hr>

<h3>Layouts</h3>

<a href="{{ route('track-layouts.create', ['track_id' => $track->id]) }}">
    <button>Add Layout</button>
</a>

<ul>
    @foreach($track->layouts as $layout)
    <li>
        {{ $layout->name }}
        ({{ $layout->active_from ?? '?' }} -
        {{ $layout->active_to ?? 'Present' }})

        <a href="{{ route('track-layouts.edit', $layout) }}">
            <button>Edit</button>
        </a>
    </li>
    @endforeach
</ul>


@endif

@endsection