<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\World;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index()
    {
        $worldId = session('active_world_id');

        if (!$worldId) {
            return redirect()->route('world.select');
        }

        $world = World::findOrFail($worldId);

        return redirect()->route('worlds.constructors.index', $world);
    }

    public function create()
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        return Inertia::render('teams/create', [
            'world' => $world,
        ]);
    }

    public function store(Request $request)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        $validated = $request->validate([
            'name' => 'required|max:255',
            'base_country' => 'nullable|max:255',
            'active' => 'boolean',
        ]);

        $world->teams()->create($validated);

        return redirect()->route('worlds.constructors.index', $world)
            ->with('success', 'Team created.');
    }

    public function show(Team $team)
    {
        return view('teams.show', compact('team'));
    }

    public function edit(Team $team)
    {
        $worlds = World::all();
        return view('teams.edit', compact('team', 'worlds'));
    }

    public function update(Request $request, Team $team)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'name' => 'required|max:255',
            'base_country' => 'nullable|max:255',
            'active' => 'boolean',
        ]);

        $team->update($validated);

        return redirect()->route('teams.index')
            ->with('success', 'Team updated.');
    }

    public function destroy(Team $team)
    {
        $team->delete();
        return redirect()->route('teams.index')
            ->with('success', 'Team deleted.');
    }
}

