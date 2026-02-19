<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\SeasonEntry;
use App\Models\Season;
use App\Models\World;
use App\Models\EntryClass;


class EntryClassController extends Controller
{
    //
    public function store(Request $request, World $world, Season $season, SeasonEntry $seasonEntry)
    {
        $validated = $request->validate([
            'race_class_id' => 'required|exists:season_classes,id',
        ]);

        $seasonEntry->entryClasses()->create([
            'race_class_id' => $validated['race_class_id'],
        ]);

        return back()->with('success', 'Class assigned successfully.');
    }

    public function destroy(World $world, Season $season, SeasonEntry $seasonEntry, EntryClass $entryClass)
    {
        $entryClass->delete();

        return back()->with('success', 'Class removed.');
    }
}
