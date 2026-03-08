<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Series;
use App\Models\Season;
use App\Models\CalendarRace;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $worldId = session('active_world_id');

        $world = World::findOrFail($worldId);

        $currentYear = $world->current_year;

        // Get seasons for current year with their series
        $seasons = Season::whereHas('series', function ($query) use ($worldId) {
                $query->where('world_id', $worldId);
            })
            ->with('series')
            ->orderBy('year', 'asc')
            ->get();

        $upcomingRaces = CalendarRace::with(['season.series','trackLayout.track'])
            ->where('is_locked', 0)
            ->whereHas('season', function ($query) use ($currentYear) {
                $query->where('year','>=' , $currentYear);
            })
            ->orderBy('race_date', 'asc')
            ->get();


        return view('dashboard.index', compact(
            'world',
            'currentYear',
            'seasons',
            'upcomingRaces'
        ));
    }
}
