<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Result;
use App\Models\CarEntry;
use App\Models\RaceSession;

class ResultController extends Controller
{
    public function index()
    {
        $results = Result::with(['raceSession', 'carEntry'])
            ->paginate(25);

        return view('results.index', compact('results'));
    }

    public function create()
    {
        $sessions = RaceSession::all();
        $carEntries = CarEntry::all();

        return view('results.create', compact('sessions', 'carEntries'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'race_session_id' => 'required|exists:race_sessions,id',
            'car_entry_id' => 'required|exists:car_entries,id',
            'position' => 'nullable|integer',
            'grid_position' => 'nullable|integer',
            'laps_completed' => 'nullable|integer',
            'status' => 'nullable|max:100',
            'lap_time_ms' => 'nullable|integer',
            'points_awarded' => 'nullable|numeric',
        ]);
        
        Result::create($validated);

        return redirect()->route('results.index')
            ->with('success', 'Result added.');
    }

    public function show(Result $result)
    {
        return view('results.show', compact('result'));
    }

    public function edit(Result $result)
    {
        $sessions = RaceSession::all();
        $carEntries = CarEntry::all();

        return view('results.edit', compact('result', 'sessions', 'carEntries'));
    }

    public function update(Request $request, Result $result)
    {
        $validated = $request->validate([
            'race_session_id' => 'required|exists:race_sessions,id',
            'car_entry_id' => 'required|exists:car_entries,id',
            'position' => 'nullable|integer',
            'grid_position' => 'nullable|integer',
            'laps_completed' => 'nullable|integer',
            'status' => 'nullable|max:100',
            'lap_time_ms' => 'nullable|integer',
            'points_awarded' => 'nullable|numeric',
        ]);

        $result->update($validated);

        return redirect()->route('results.index')
            ->with('success', 'Result updated.');
    }

    public function destroy(Result $result)
    {
        $result->delete();

        return redirect()->route('results.index')
            ->with('success', 'Result deleted.');
    }
}

