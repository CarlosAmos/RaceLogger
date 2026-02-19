@extends('layouts.app')

@section('content')

<h1>{{ $world->name }} Dashboard</h1>

<p>
    Current Year: <strong>{{ $currentYear }}</strong>
</p>

<hr>

@if($seasons->count() === 0)

<div style="padding: 20px; background: #fff3cd; border-radius: 6px;">
    <strong>No active series for {{ $currentYear }}.</strong>
    <br><br>

    <a href="{{ route('series.create') }}">
        <button>Create Series</button>
    </a>
</div>

@else

<h3>Active Series</h3>

<div style="display: flex; gap: 15px; flex-wrap: wrap;">

    @foreach($seasons as $season)

    <div style="border:1px solid #ccc; padding:15px; width:220px; background:#fff;">

        <h4>{{ $season->series->name }}</h4>

        <div style="margin-top:10px;">
            <a href="{{ route('seasons.show', $season->id) }}">
                <button>Open Season</button>
            </a>

            <a href="{{ route('seasons.edit', $season->id, $world) }}">
                <button>Edit</button>
            </a>
        </div>

    </div>

    @endforeach

</div>


@endif

@endsection