@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $world->name }} - Teams</h1>

    <a href="{{ route('worlds.engines.index', $world) }}"
        class="btn btn-outline-dark mb-3">
        Manage Engines
    </a>

    <a href="{{ route('worlds.constructors.create', $world) }}"
        class="btn btn-primary mb-3">
        Create Team
    </a>

    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between">
            <h4>Constructors</h4>
            <a href="{{ route('worlds.constructors.create', $world) }}"
                class="btn btn-primary btn-sm">
                Add Constructor
            </a>
        </div>

        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Country</th>
                        <th class="text-end">Car Models</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($constructors as $constructor)
                    <tr>
                        <td>{{ $constructor->name }}</td>
                        <td>{{ optional($constructor->country)->name }}</td>
                        <td class="text-end">
                            <a href="{{ route('worlds.constructors.car-models.index', [$world, $constructor]) }}"
                                class="btn btn-sm btn-outline-secondary">
                                View Models
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">No constructors yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h4>Entrants</h4>
            <a href="{{ route('worlds.entrants.create', $world) }}"
                class="btn btn-success btn-sm">
                Add Entrant
            </a>
        </div>

        <div class="card-body p-0">
            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Country</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entrants as $entrant)
                    <tr>
                        <td>{{ $entrant->name }}</td>
                        <td>{{ optional($entrant->country)->name }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3">No entrants yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>


</div>
@endsection