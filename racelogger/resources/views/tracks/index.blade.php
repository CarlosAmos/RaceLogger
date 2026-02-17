@extends('layouts.app')

@section('content')

<h1>Circuits</h1>

<a href="{{ route('tracks.create') }}">
    <button>Add New Circuit</button>
</a>

<hr>

@foreach($tracks as $track)
    <div style="border:1px solid #ccc; padding:10px; margin-bottom:10px;">
    <strong>{{ $track->name }}</strong><br>
    {{ $track->city ?? 'Unknown City' }},
    {{ $track->country?->name ?? 'Unknown Country' }}



        <br><br>

        Layouts:
        <ul>
            @foreach($track->layouts as $layout)
                <li>
                    {{ $layout->name }}
                    ({{ $layout->active_from ?? '?' }} - {{ $layout->active_to ?? 'Present' }})
                </li>
            @endforeach
        </ul>

        <a href="{{ route('tracks.edit', $track) }}">
            <button>Edit</button>
        </a>
    </div>
@endforeach

@endsection
