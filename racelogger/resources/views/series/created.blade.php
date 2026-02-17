@extends('layouts.app')

@section('content')

<h1>{{ $series->name }} Created 🎉</h1>

@if($series->is_multiclass)
    <p>This is a Multi-Class Championship.</p>
@endif

<hr>

<h3>Would you like to create the first season?</h3>

<br>

<div>
    <a href="{{ route('seasons.create') }}?series_id={{ $series->id }}">
        <button>Yes, Create Season</button>
    </a>

    <a href="{{ route('dashboard') }}">
        <button>No, Go To Dashboard</button>
    </a>
</div>

@endsection
