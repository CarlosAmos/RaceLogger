<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Country;
use App\Models\Team;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ConstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(World $world)
    {
        $constructors = $world->constructors()
            ->with('country')
            ->orderBy('name')
            ->get();

        $entrants = $world->entrants()
            ->with('country')
            ->orderBy('name')
            ->get();

        $teams = $world->teams()
            ->orderBy('name')
            ->get();

        return Inertia::render('constructors/index', [
            'world' => $world,
            'constructors' => $constructors,
            'entrants' => $entrants,
            'teams' => $teams,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(World $world)
    {
        $countries = Country::orderBy('name')->get();

        return Inertia::render('constructors/create', compact('world', 'countries'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'color' => 'nullable|string|max:7',
        ]);

        $world->constructors()->create($validated);

        return redirect()
            ->route('worlds.constructors.index', $world)
            ->with('success', 'Team created successfully.');
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
