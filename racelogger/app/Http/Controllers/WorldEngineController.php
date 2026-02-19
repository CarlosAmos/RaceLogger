<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\World;

class WorldEngineController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(World $world)
    {
        $engines = $world->engines()
            ->orderBy('name')
            ->get();

        return view('engines.index', compact('world', 'engines'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(World $world)
    {
        return view('engines.create', compact('world'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'configuration' => 'nullable|string|max:50',
            'capacity' => 'nullable|string|max:50',
        ]);

        $world->engines()->create([
            ...$validated,
            'hybrid' => $request->has('hybrid'),
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
