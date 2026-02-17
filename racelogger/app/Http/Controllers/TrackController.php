<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Track;
use App\Models\Country;

class TrackController extends Controller
{
    public function index()
    {
        $tracks = Track::with('layouts')->get();

        return view('tracks.index', compact('tracks'));
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return view('tracks.form', [
            'track' => new Track(),
            'countries' => $countries,
            'mode' => 'create'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $track = Track::create($validated);

        return redirect()->route('tracks.edit', $track)
        ->with('success', 'Track created. Now add layouts.');
    }

    public function edit(Track $track)
    {
        $countries = Country::orderBy('name')->get();

        return view('tracks.form', [
            'track' => $track,
            'countries' => $countries,
            'mode' => 'edit'
        ]);
    }

    public function update(Request $request, Track $track)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'city' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $track->update($validated);

    }
}
