@extends('layouts.app')

@section('content')
<div class="container">

    <h2 class="mb-4">
        Car #{{ $entryCar->car_number }} – Driver Assignment
    </h2>

    <form method="POST"
        action="{{ route(
            'entry-cars.drivers.update',
            [$world, $season, $seasonEntry, $entryClass, $entryCar]
        ) }}">
        @csrf

        {{-- TOP SAVE BUTTON --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="mb-0">Currently Assigned</h5>

            <button class="btn btn-primary">
                Save Drivers
            </button>
        </div>

        {{-- Assigned Drivers --}}
        @if($entryCar->drivers->count())
        <div class="d-flex flex-wrap gap-2 mb-4">
            @foreach($entryCar->drivers as $driver)
            <div class="badge bg-success p-2">
                {{ $driver->full_name }}
                ({{ $driver->country->iso_code ?? '' }})
            </div>
            @endforeach
        </div>
        @else
        <p class="text-muted mb-4">No drivers assigned.</p>
        @endif

        <hr>

        {{-- Driver Picker --}}
        <h5 class="mb-3">Available Drivers</h5>

        <div class="row">

            @foreach($drivers as $driver)

            @php
            $isAssignedToThisCar = in_array($driver->id, $assignedDrivers);
            $isAssignedToOtherCar = in_array($driver->id, $otherCarDriverIds);
            @endphp

            <div class="col-md-3 mb-3">

                <label class="card h-100 p-2
            {{ $isAssignedToThisCar ? 'border-success' : '' }}
            {{ $isAssignedToOtherCar && !$isAssignedToThisCar ? 'bg-light text-muted border-secondary' : '' }}"
                    style="cursor:pointer;">

                    <div class="form-check">

                        <input type="checkbox"
                            name="drivers[]"
                            value="{{ $driver->id }}"
                            class="form-check-input"
                            {{ $isAssignedToThisCar ? 'checked' : '' }}
                            {{ $isAssignedToOtherCar && !$isAssignedToThisCar ? 'disabled' : '' }}>

                        <span class="form-check-label fw-bold">
                            {{ $driver->full_name }}
                        </span>

                    </div>

                    <small>
                        {{ $driver->country->name ?? '' }}
                    </small>

                </label>

            </div>

            @endforeach

        </div>

        {{-- BOTTOM SAVE BUTTON --}}
        <div class="mt-4">
            <button class="btn btn-primary">
                Save Drivers
            </button>
        </div>

    </form>

</div>
@endsection