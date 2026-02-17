<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\World;

class WorldController extends Controller
{
    public function index()
    {
        $worlds = World::latest()->paginate(10);
        return view('worlds.index', compact('worlds'));
    }

    public function create()
    {
        return view('worlds.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|min:1900',
        ]);

        World::create($validated);

        return redirect()->route('worlds.index')
            ->with('success', 'World created successfully.');
    }

    public function show(World $world)
    {
        return view('worlds.show', compact('world'));
    }

    public function edit(World $world)
    {
        return view('worlds.edit', compact('world'));
    }

    public function update(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|min:1900',
        ]);

        $world->update($validated);

        return redirect()->route('worlds.index')
            ->with('success', 'World updated.');
    }

    public function destroy(World $world)
    {
        $world->delete();

        return redirect()->route('worlds.index')
            ->with('success', 'World deleted.');
    }
}

