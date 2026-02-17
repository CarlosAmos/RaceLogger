<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PointsSystem;
use App\Models\Season;

class PointsSystemController extends Controller
{
    public function index()
    {
        $systems = PointsSystem::with('season')
            ->paginate(15);

        return view('points-systems.index', compact('systems'));
    }

    public function create()
    {
        $seasons = Season::all();

        return view('points-systems.create', compact('seasons'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'name' => 'required|max:255',
            'fastest_lap_enabled' => 'boolean',
            'fastest_lap_points' => 'nullable|integer',
            'fastest_lap_min_position' => 'nullable|integer',
            'pole_position_enabled' => 'boolean',
            'pole_position_points' => 'nullable|integer',
            'quali_bonus_enabled' => 'boolean',
        ]);

        PointsSystem::create($validated);

        return redirect()->route('points-systems.index')
            ->with('success', 'Points system created.');
    }

    public function show(PointsSystem $pointsSystem)
    {
        return view('points-systems.show', compact('pointsSystem'));
    }

    public function edit(PointsSystem $pointsSystem)
    {
        $seasons = Season::all();

        return view('points-systems.edit', compact('pointsSystem', 'seasons'));
    }

    public function update(Request $request, PointsSystem $pointsSystem)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'name' => 'required|max:255',
            'fastest_lap_enabled' => 'boolean',
            'fastest_lap_points' => 'nullable|integer',
            'fastest_lap_min_position' => 'nullable|integer',
            'pole_position_enabled' => 'boolean',
            'pole_position_points' => 'nullable|integer',
            'quali_bonus_enabled' => 'boolean',
        ]);

        $pointsSystem->update($validated);

        return redirect()->route('points-systems.index')
            ->with('success', 'Points system updated.');
    }

    public function destroy(PointsSystem $pointsSystem)
    {
        $pointsSystem->delete();

        return redirect()->route('points-systems.index')
            ->with('success', 'Points system deleted.');
    }
}

