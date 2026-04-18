<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\Track;
use App\Models\Country;
use Inertia\Inertia;

class TrackController extends Controller
{
    public function index()
    {
        $tracks = Track::with(['layouts', 'country'])->get();

        return Inertia::render('tracks/index', ['tracks' => $tracks]);
    }

    public function create()
    {
        $countries = Country::orderBy('name')->get();

        return Inertia::render('tracks/form', [
            'track' => new Track(),
            'countries' => $countries,
            'mode' => 'create'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_short' => 'nullable|string|max:50',
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
        $track->load('layouts');

        return Inertia::render('tracks/form', [
            'track' => $track,
            'countries' => $countries,
            'mode' => 'edit'
        ]);
    }

    public function update(Request $request, Track $track)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'name_short' => 'nullable|string|max:50',
            'city' => 'nullable|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $track->update($validated);

    }
}
