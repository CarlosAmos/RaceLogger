<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\Season;

class SeasonController extends Controller
{
    public function index()
    {
        $seasons = Season::with('series.world')->paginate(15);
        return view('seasons.index', compact('seasons'));
    }

    public function create()
    {
        $series = Series::all();
        return view('seasons.create', compact('series'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'series_id' => 'required|exists:series,id',
            'year' => 'required|integer',
            'is_simulated' => 'boolean',
        ]);

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
        $series = Series::all();
        return view('seasons.edit', compact('season', 'series'));
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

