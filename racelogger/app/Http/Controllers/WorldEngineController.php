<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\World;
use App\Models\Constructor;
use Inertia\Inertia;

class WorldEngineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(World $world)
    {
        $engines = $world->engines()
            ->with('manufacturer')
            ->orderBy('name')
            ->get();

        return Inertia::render('engines/index', compact('world', 'engines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(World $world)
    {
        $constructors = Constructor::where('world_id', $world->id)->orderBy('name')->get(['id', 'name']);

        return Inertia::render('engines/create', compact('world', 'constructors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'constructor_id' => 'nullable|integer|exists:constructors,id',
            'configuration'  => 'nullable|string|max:50',
            'capacity'       => 'nullable|string|max:50',
        ]);

        $world->engines()->create([
            ...$validated,
            'hybrid' => $request->boolean('hybrid'),
        ]);

        return redirect()
            ->route('worlds.engines.index', $world)
            ->with('success', 'Engine created successfully.');
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
