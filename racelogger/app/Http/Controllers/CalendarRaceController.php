<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CalendarRace;
use App\Models\Season;
use App\Models\TrackLayout;

class CalendarRaceController extends Controller
{
    public function index()
    {
        $races = CalendarRace::with(['season', 'trackLayout'])
            ->orderBy('race_date')
            ->paginate(20);

        return view('calendar-races.index', compact('races'));
    }

    public function create()
    {
        $seasons = Season::all();
        $layouts = TrackLayout::all();

        return view('calendar-races.create', compact('seasons', 'layouts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'track_layout_id' => 'required|exists:track_layouts,id',
            'round_number' => 'required|integer',
            'name' => 'required|max:255',
            'race_date' => 'required|date',
        ]);

        CalendarRace::create($validated);

        return redirect()->route('calendar-races.index')
            ->with('success', 'Race created.');
    }

    public function show(CalendarRace $calendarRace)
    {
        return view('calendar-races.show', compact('calendarRace'));
    }

    public function edit(CalendarRace $calendarRace)
    {
        $seasons = Season::all();
        $layouts = TrackLayout::all();

        return view('calendar-races.edit', compact('calendarRace', 'seasons', 'layouts'));
    }

    public function update(Request $request, CalendarRace $calendarRace)
    {
        $validated = $request->validate([
            'season_id' => 'required|exists:seasons,id',
            'track_layout_id' => 'required|exists:track_layouts,id',
            'round_number' => 'required|integer',
            'name' => 'required|max:255',
            'race_date' => 'required|date',
        ]);

        $calendarRace->update($validated);

        return redirect()->route('calendar-races.index')
            ->with('success', 'Race updated.');
    }

    public function destroy(CalendarRace $calendarRace)
    {
        $calendarRace->delete();

        return redirect()->route('calendar-races.index')
            ->with('success', 'Race deleted.');
    }
}

