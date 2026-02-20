@extends('layouts.app')

@section('content')
<div class="container">

    <div class="mb-3">
        <a href="{{ route('seasons.show', [$season]) }}"
           class="btn btn-secondary btn-sm">
            ← Back to Classes
        </a>
    </div>
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>
            {{ $entryClass->name }} – Entry Cars
        </h2>

        <a href="{{ route(
            'worlds.seasons.season-entries.entry-classes.entry-cars.create',
            [$world, $season, $seasonEntry, $entryClass]
        ) }}"
           class="btn btn-primary">
            + Add Entry Car
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Car #</th>
                        <th>Model</th>
                        <th>Engine</th>
                        <th>Livery</th>
                        <th>Drivers</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entryCars as $car)
                        <tr>
                            <td>#{{ $car->car_number }}</td>
                            <td>{{ $car->carModel->name }}</td>
                            <td>{{ $car->carModel->engine->name ?? '-' }}</td>
                            <td>{{ $car->livery_name ?? '-' }}</td>
                            <td>
                                <a href="{{ route('entry-cars.drivers.edit', [$world, $season, $seasonEntry, $entryClass, $car]) }}"
                                class="btn btn-sm btn-outline-primary">
                                    Drivers
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">No entry cars yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection