@extends('layouts.app')

@section('content')
<h1>Select World</h1>

<a href="{{ route('worlds.create') }}">Create New World</a>

<hr>

@foreach($worlds as $world)
    <div style="margin-bottom: 10px;">
        <strong>{{ $world->name }}</strong>
        (Start Year: {{ $world->start_year }})

        <form action="{{ route('world.select.store', $world) }}" method="POST" style="display:inline;">
            @csrf
            <button type="submit">Select</button>
        </form>
    </div>
@endforeach

@endsection
