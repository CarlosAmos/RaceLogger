<?php

namespace App\Http\Controllers;

use App\Models\PointSystem;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PointSystemController extends Controller
{
    public function create(Request $request)
    {
        return Inertia::render('point-systems/create', [
            'seasonId' => $request->query('season_id'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $pointSystem = PointSystem::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // Race Points
        if ($request->race_points) {
            foreach ($request->race_points as $position => $points) {
                if ($points !== null && $points !== '') {
                    $pointSystem->rules()->create([
                        'type' => 'race',
                        'position' => $position,
                        'points' => $points,
                    ]);
                }
            }
        }

        // Qualifying Points
        if ($request->enable_qualifying && $request->qualifying_points) {
            foreach ($request->qualifying_points as $position => $points) {
                if ($points !== null && $points !== '') {
                    $pointSystem->rules()->create([
                        'type' => 'qualifying',
                        'position' => $position,
                        'points' => $points,
                    ]);
                }
            }
        }

        // Fastest Lap
        if ($request->enable_fastest_lap) {
            $pointSystem->bonusRules()->create([
                'type' => 'fastest_lap',
                'points' => $request->fastest_lap_points ?? 0,
            ]);
        }

        // 🔥 Redirect back to season editor if provided
        if ($request->season_id) {
            return redirect()->route('seasons.edit', $request->season_id)
                ->with('success', 'Point system created successfully.');
        }

        return redirect()->route('seasons.index');
    }
}
