<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\Season;
use App\Models\World;
use App\Models\TrackLayout;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::with('series.world')->paginate(15);
        return view('seasons.index', compact('seasons'));
    }

    public function create(Request $request)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);

        $seriesId = $request->query('series_id');

        $series = Series::where('world_id', $worldId)->get();

        $defaultYear = $world->current_year;

        $seasonYear = $defaultYear; // for create
        // OR $season->year for edit

        $layouts = TrackLayout::with('track')
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $seasonYear);
            })
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $seasonYear);
            })
            ->get();

        return view('seasons.form', [
            'season' => new Season(),
            'series' => $series,
            'seriesId' => $seriesId,
            'defaultYear' => $defaultYear,
            'layouts' => $layouts,
            'mode' => 'create'
        ]);
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer',
        ]);

        // Extra validation
        // $exists = Season::where('series_id', $request->series_id)
        //     ->where('year', $request->year)
        //     ->exists();

        // if ($exists) {
        //     return back()->withErrors([
        //         'year' => 'A season for this year already exists for this series.'
        //     ])->withInput();
        // }
        Season::create($validated);

        return redirect()->route('seasons.index')
            ->with('success', 'Season created.');
    }

    public function show(Season $season)
    {
        return view('seasons.show', compact('season'));
    }

    public function edit(Season $season)
    {
        $worldId = session('active_world_id');
        $world = World::findOrFail($worldId);
        
        abort_unless($season->series->world_id == $worldId, 403);

        $series = Series::where('world_id', $worldId)->get();

        $defaultYear = $world->current_year;
        $seasonYear = $defaultYear; // for create
        // OR $season->year for edit

        $layouts = TrackLayout::with('track')
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_from')
                    ->orWhere('active_from', '<=', $seasonYear);
            })
            ->where(function ($query) use ($seasonYear) {
                $query->whereNull('active_to')
                    ->orWhere('active_to', '>=', $seasonYear);
            })
            ->get();

        return view('seasons.form', [
            'season' => $season,
            'series' => $series,
            'seriesId' => $season->series_id,
            'defaultYear' => $season->year,
            'layouts' => $layouts,
            'mode' => 'edit'
        ]);
    }

    public function update(Request $request, Season $season)
    {
        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer',
            'is_simulated' => 'boolean',
        ]);

        $season->update($validated);

        return redirect()->route('seasons.index')
            ->with('success', 'Season updated.');
    }

    public function destroy(Season $season)
    {
        $season->delete();

        return redirect()->route('seasons.index')
            ->with('success', 'Season deleted.');
    }
}

