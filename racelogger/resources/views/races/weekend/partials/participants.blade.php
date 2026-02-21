@php
    // Collect all season cars
    $seasonCars = collect();

    foreach ($race->season->seasonEntries as $entry) {
        foreach ($entry->entryClasses as $entryClass) {
            foreach ($entryClass->entryCars as $car) {
                $seasonCars->push($car);
            }
        }
    }

    // Group by class and sort by display_order
    $classGroups = $seasonCars->groupBy(function($car) {
        return $car->entryClass->raceClass->id;
    })->map(function($cars) {
        return [
            'name' => $cars->first()->entryClass->raceClass->name,
            'display_order' => $cars->first()->entryClass->raceClass->display_order ?? 0,
            'cars' => $cars->sortBy('car_number')->values()
        ];
    })->sortBy('display_order')->values();

    $multipleClasses = $classGroups->count() > 1;

    $selectedCars = $race->entryCars->pluck('id')->toArray();
@endphp

<div class="card shadow-sm">
    <div class="card-body">

        <h5 class="mb-4">Select Race Participants</h5>

        @foreach($classGroups as $group)

            {{-- Class Header + Select All --}}
            @if($multipleClasses)
                <div class="d-flex justify-content-between align-items-center mb-2 border-bottom pb-2">
                    <h6 class="fw-bold text-uppercase mb-0">
                        {{ $group['name'] }}
                    </h6>

                    <button type="button"
                            class="btn btn-sm btn-outline-secondary select-all-btn"
                            data-class-index="{{ $loop->index }}">
                        Select All
                    </button>
                </div>
            @endif

            <div class="row mb-4 class-group"
                 data-class-index="{{ $loop->index }}">

                @foreach($group['cars'] as $car)

                    <div class="col-md-4 mb-2">
                        <div class="form-check">

                            <input class="form-check-input"
                                   type="checkbox"
                                   name="participants[]"
                                   value="{{ $car->id }}"
                                   @checked(in_array($car->id, $selectedCars))>

                            <label class="form-check-label">
                                <strong>#{{ $car->car_number }}</strong>
                                {{ $car->livery_name
                                    ?? $car->entryClass->seasonEntry->entrant->name }}
                            </label>

                        </div>
                    </div>

                @endforeach

            </div>

        @endforeach

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.select-all-btn')
        .forEach(button => {

            button.addEventListener('click', function () {

                const index = button.dataset.classIndex;

                const group = document.querySelector(
                    `.class-group[data-class-index="${index}"]`
                );

                const checkboxes = group.querySelectorAll('input[type="checkbox"]');

                const allChecked = Array.from(checkboxes)
                    .every(cb => cb.checked);

                checkboxes.forEach(cb => cb.checked = !allChecked);

                button.textContent = allChecked ? 'Select All' : 'Deselect All';

            });

        });

});
</script>