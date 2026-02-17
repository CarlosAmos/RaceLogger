<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Team;
use App\Models\World;

class TeamController extends Controller
{
    public function index()
    {
        $teams = Team::with('world')->paginate(15);
        return view('teams.index', compact('teams'));
    }

    public function create()
    {
        $worlds = World::all();
        return view('teams.create', compact('worlds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'name' => 'required|max:255',
            'base_country' => 'nullable|max:255',
            'active' => 'boolean',
        ]);

        Team::create($validated);

        return redirect()->route('teams.index')
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

