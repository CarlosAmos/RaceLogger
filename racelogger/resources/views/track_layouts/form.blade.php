@extends('layouts.app')

@section('content')

<h1>
    {{ $mode === 'create' ? 'Add Layout' : 'Edit Layout' }}
</h1>

<h3>Track: {{ $track->name }}</h3>

<form method="POST"
      action="{{ $mode === 'create'
                ? route('track-layouts.store')
                : route('track-layouts.update', $layout) }}">

    @csrf
    @if($mode === 'edit')
        @method('PUT')
    @endif

    @if($mode === 'create')
        <input type="hidden" name="track_id" value="{{ $track->id }}">
    @endif

    <div>
        <label>Layout Name</label><br>
        <input type="text"
               name="name"
               value="{{ old('name', $layout->name) }}"
               required>
    </div>

    <br>

    <div>
        <label>Length (km)</label><br>
        <input type="number"
               step="0.001"
               name="length_km"
               value="{{ old('length_km', $layout->length_km) }}">
    </div>

    <br>

    <div>
        <label>Active From (Year)</label><br>
        <input type="number"
               name="active_from"
               value="{{ old('active_from', $layout->active_from) }}">
    </div>

    <br>

    <div>
        <label>Active To (Year)</label><br>
        <input type="number"
               name="active_to"
               value="{{ old('active_to', $layout->active_to) }}">
    </div>

    <br>

    <button type="submit">
        {{ $mode === 'create' ? 'Create Layout' : 'Update Layout' }}
    </button>

</form>

@if($mode === 'edit')
    <br>
    <form method="POST"
          action="{{ route('track-layouts.destroy', $layout) }}"
          onsubmit="return confirm('Delete this layout?')">
        @csrf
        @method('DELETE')
        <button type="submit">Delete Layout</button>
    </form>
@endif

@endsection
