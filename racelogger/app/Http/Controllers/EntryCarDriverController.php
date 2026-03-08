<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Season;
use App\Models\SeasonEntry;
use App\Models\EntryClass;
use App\Models\EntryCar;
use App\Models\Driver;
use Illuminate\Http\Request;

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
        // Drivers assigned anywhere else in this season
        $otherCarDriverIds = $season->seasonEntries()
            ->with('entryClasses.entryCars.drivers')
            ->get()
            ->pluck('entryClasses')
            ->flatten()
            ->pluck('entryCars')
            ->flatten()
            ->where('id', '!=', $entryCar->id)
            ->pluck('drivers')
            ->flatten()
            ->pluck('id')
            ->unique()
            ->toArray();

        return view('entry-car-drivers.edit', compact(
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
