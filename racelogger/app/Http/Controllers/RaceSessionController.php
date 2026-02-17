<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\RaceSession;
use App\Models\CalendarRace;

class RaceSessionController extends Controller
{
    public function index()
    {
        $sessions = RaceSession::with('calendarRace')
            ->orderBy('session_order')
            ->paginate(20);

        return view('race-sessions.index', compact('sessions'));
    }

    public function create()
    {
        $races = CalendarRace::all();

        return view('race-sessions.create', compact('races'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'calendar_race_id' => 'required|exists:calendar_races,id',
            'name' => 'required|max:255',
            'type' => 'required|max:50',
            'session_order' => 'required|integer',
        ]);

        RaceSession::create($validated);

        return redirect()->route('race-sessions.index')
            ->with('success', 'Session created.');
    }

    public function show(RaceSession $raceSession)
    {
        return view('race-sessions.show', compact('raceSession'));
    }

    public function edit(RaceSession $raceSession)
    {
        $races = CalendarRace::all();

        return view('race-sessions.edit', compact('raceSession', 'races'));
    }

    public function update(Request $request, RaceSession $raceSession)
    {
        $validated = $request->validate([
            'calendar_race_id' => 'required|exists:calendar_races,id',
            'name' => 'required|max:255',
            'type' => 'required|max:50',
            'session_order' => 'required|integer',
        ]);

        $raceSession->update($validated);

        return redirect()->route('race-sessions.index')
            ->with('success', 'Session updated.');
    }

    public function destroy(RaceSession $raceSession)
    {
        $raceSession->delete();

        return redirect()->route('race-sessions.index')
            ->with('success', 'Session deleted.');
    }
}

