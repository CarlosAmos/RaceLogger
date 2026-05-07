<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Season;
use App\Models\SeasonEntry;
use App\Models\EntryClass;
use App\Models\EntryCar;
use App\Models\Driver;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EntryCarDriverController extends Controller
{

    public function edit(
        World $world,
        Season $season,
        SeasonEntry $seasonEntry,
        EntryClass $entryClass,
        EntryCar $entryCar
    ) {
        $drivers = $world->drivers()
            ->with('country')
            ->orderBy('first_name')
            ->get();

        // Drivers assigned to THIS car
        $assignedDrivers = $entryCar->drivers()
            ->pluck('drivers.id')
            ->toArray();
        $drivers->sortBy('first_name');
        // Drivers assigned to a DIFFERENT seat this season.
        // Exclude all entry_cars sharing the same car_number + entry_class (different effective_from_round
        // records are the same physical seat, just a mid-season car change).
        $otherCarDriverIds = $season->seasonEntries()
            ->with('entryClasses.entryCars.drivers')
            ->get()
            ->pluck('entryClasses')
            ->flatten()
            ->pluck('entryCars')
            ->flatten()
            ->filter(fn ($car) =>
                $car->id !== $entryCar->id &&
                !(
                    $car->entry_class_id === $entryCar->entry_class_id &&
                    $car->car_number     === $entryCar->car_number
                )
            )
            ->pluck('drivers')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();

        return Inertia::render('entry-car-drivers/edit', compact(
            'world',
            'season',
            'seasonEntry',
            'entryClass',
            'entryCar',
            'drivers',
            'assignedDrivers',
            'otherCarDriverIds'
        ));
    }

    public function update(
        Request $request,
        World $world,
        Season $season,
        SeasonEntry $seasonEntry,
        EntryClass $entryClass,
        EntryCar $entryCar
    ) {
        $validated = $request->validate([
            'drivers' => 'nullable|array',
            'drivers.*' => 'exists:drivers,id',
        ]);

        $entryCar->drivers()->sync($validated['drivers'] ?? []);

        return redirect()
            ->route(
                'worlds.seasons.edit',
                [$world, $season]
            )
            ->with('success', 'Drivers updated successfully.');
    }
}
