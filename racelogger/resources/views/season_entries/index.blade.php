@extends('layouts.app')

@section('content')
<div class="container">

    <h1>{{ $season->name }} – Teams</h1>

    <a href="{{ route('worlds.seasons.season-entries.create', [$world, $season]) }}"
       class="btn btn-primary mb-3">
        Add Team
    </a>

    <table class="table">
        <thead>
            <tr>
                <th>Entrant</th>
                <th>Constructor</th>
                <th>Display Name</th>
            </tr>
        </thead>
        <tbody>
            @forelse($season->seasonEntries as $entry)
                <tr>
                    <td>{{ $entry->entrant->name }}</td>
                    <td>{{ $entry->constructor->name }}</td>
                    <td>{{ $entry->display_name ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3">No teams added yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
