<?php

namespace App\Http\Controllers;

use App\Models\World;
use Illuminate\Http\Request;

class WorldController extends Controller
{
    // World Select Page
    public function index()
    {
        $worlds = World::orderBy('created_at', 'desc')->get();

        return view('worlds.index', compact('worlds'));
    }

    // Create Form
    public function create()
    {
        return view('worlds.create');
    }

    // Store New World
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|min:1900',
        ]);

        $world = World::create([
            'name' => $validated['name'],
            'start_year' => $validated['start_year'],
            'current_year' => $validated['start_year'], 
            'is_canonical' => false,
        ]);

        // Set as active world
        session(['active_world_id' => $world->id]);

        return redirect()->route('worlds.created', $world);
    }

    // After Creation Page
    public function created(World $world)
    {
        return view('worlds.created', compact('world'));
    }

    // Select Existing World
    public function select(World $world)
    {
        session(['active_world_id' => $world->id]);

        return redirect()->route('dashboard');
    }

    // Edit
    public function edit(World $world)
    {
        return view('worlds.edit', compact('world'));
    }

    // Update
    public function update(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'start_year' => 'required|integer|min:1900',
        ]);

        $world->update($validated);

        return redirect()->route('world.select')
            ->with('success', 'World updated.');
    }

    // Delete (protect canonical)
    public function destroy(World $world)
    {
        if ($world->is_canonical) {
            return redirect()->back()
                ->with('error', 'Canonical world cannot be deleted.');
        }

        $world->delete();

        return redirect()->route('world.select')
            ->with('success', 'World deleted.');
    }
}
