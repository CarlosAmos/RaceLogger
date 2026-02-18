@extends('layouts.app')

@section('content')
<div class="container">
    <h1>{{ $world->name }} - Teams</h1>

    <a href="{{ route('worlds.constructors.create', $world) }}" 
       class="btn btn-primary mb-3">
        Create Team
    </a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Country</th>
                <th>Color</th>
            </tr>
        </thead>
        <tbody>
            @forelse($constructors as $constructor)
                <tr>
                    <td>{{ $constructor->name }}</td>
                    <td>{{ $constructor->country }}</td>
                    <td>
                        @if($constructor->color)
                            <span style="background: {{ $constructor->color }};
                                         display:inline-block;
                                         width:20px;
                                         height:20px;"></span>
                            {{ $constructor->color }}
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">No teams created yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

</div>
@endsection
