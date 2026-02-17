<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Series;
use App\Models\Season;

class DashboardController extends Controller
{
    public function index()
    {
        $worldId = session('active_world_id');

        $world = World::findOrFail($worldId);

        $currentYear = $world->current_year;

        // Get seasons for current year with their series
        $seasons = Season::where('year', $currentYear)
            ->whereHas('series', function ($query) use ($worldId) {
                $query->where('world_id', $worldId);
            })
            ->with('series')
            ->get();

        return view('dashboard.index', compact(
            'world',
            'currentYear',
            'seasons'
        ));
    }
}
