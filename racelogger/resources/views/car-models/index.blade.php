@extends('layouts.app')

@section('content')
<div class="container">

    <div class="mb-3">
        <a href="{{ route('worlds.constructors.index', $world) }}"
           class="btn btn-secondary btn-sm">
            ← Back to Constructors
        </a>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>{{ $constructor->name }} - Car Models</h2>
            <small class="text-muted">
                World: {{ $world->name }}
            </small>
        </div>

        <a href="{{ route('worlds.constructors.car-models.create', [$world, $constructor]) }}"
           class="btn btn-primary">
            + Add Car Model
        </a>
    </div>

    <div class="card">
        <div class="card-body p-0">

            <table class="table mb-0">
                <thead>
                    <tr>
                        <th>Model Name</th>
                        <th>Engine</th>
                        <th>Year</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($carModels as $model)
                        <tr>
                            <td>{{ $model->name }}</td>
                            <td>
                                {{ $model->engine->name ?? '-' }}
                            </td>
                            <td>{{ $model->year ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('worlds.constructors.car-models.edit', [$world, $constructor, $model]) }}"
                                   class="btn btn-sm btn-warning">
                                    Edit
                                </a>

                                <form action="{{ route('worlds.constructors.car-models.destroy', [$world, $constructor, $model]) }}"
                                      method="POST"
                                      class="d-inline">
                                    @csrf
                                    @method('DELETE')

                                    <button class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this model?')">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3">No car models created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

        </div>
    </div>

</div>
@endsection
