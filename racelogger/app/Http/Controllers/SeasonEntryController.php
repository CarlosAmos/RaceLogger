<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Constructor;
use App\Models\Entrant;
use App\Models\Season;
use App\Models\SeasonEntry;
use App\Models\World;
use Inertia\Inertia;

class SeasonEntryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(World $world, Season $season)
    {
        $entrants = $world->entrants()
            ->orderBy('name')
            ->get();

        $constructors = $world->constructors()
            ->orderBy('name')
            ->get();

        return Inertia::render('season-entries/create', compact(
            'world',
            'season',
            'entrants',
            'constructors'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world, Season $season)
    {
        $validated = $request->validate([
            'entrant_id' => 'required|exists:entrants,id',
            'constructor_id' => 'required|exists:constructors,id',
            'display_name' => 'nullable|string|max:255',
        ]);

        $season->seasonEntries()->create([
            'entrant_id'     => $validated['entrant_id'],
            'constructor_id' => $validated['constructor_id'],
            'display_name'   => $validated['display_name'] ?? null,
            'series_id'      => $season->series_id, // 👈 important
        ]);



        return redirect()
            ->route('worlds.seasons.edit', [$world, $season])
            ->with('success', 'Team added to season.');
        // return redirect()
        //     ->route('seasons.show', [$season])
        //     ->with('success', 'Team added to season.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified season entry and all its cars/drivers.
     */
    public function destroy(World $world, Season $season, SeasonEntry $seasonEntry)
    {
        $seasonEntry->delete();

        return redirect()
            ->route('worlds.seasons.edit', [$world, $season])
            ->with('success', 'Team removed from season.')
            ->setStatusCode(303);
    }
}
