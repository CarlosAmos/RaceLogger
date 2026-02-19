@extends('layouts.app')

@section('content')
<div class="container">

    <div class="mb-3">
        <a href="{{ route('worlds.constructors.index', $world) }}"
           class="btn btn-secondary btn-sm">
            ← Back to World
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>{{ $world->name }} - Engines</h2>

        <a href="{{ route('worlds.engines.create', $world) }}"
           class="btn btn-primary">
            + Add Engine
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Config</th>
                        <th>Capacity</th>
                        <th>Hybrid</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($engines as $engine)
                        <tr>
                            <td>{{ $engine->name }}</td>
                            <td>{{ $engine->configuration ?? '-' }}</td>
                            <td>{{ $engine->capacity ?? '-' }}</td>
                            <td>{{ $engine->hybrid ? 'Yes' : 'No' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No engines created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
