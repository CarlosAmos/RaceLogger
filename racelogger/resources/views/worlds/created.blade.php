@extends('layouts.app')

@section('content')

<h1>{{ $world->name }} Created!</h1>

<p>Start Year: {{ $world->start_year }}</p>

<hr>

<h3>Would you like to create your first series?</h3>

<br>

<a href="{{ route('series.create') }}">
    <button>Yes, Create Series</button>
</a>

<a href="{{ route('dashboard') }}">
    <button>No, Go to Dashboard</button>
</a>

@endsection
