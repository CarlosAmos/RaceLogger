<?php

namespace App\Http\Controllers;

use App\Models\Track;
use App\Models\TrackLayout;
use Illuminate\Http\Request;

class TrackLayoutController extends Controller
{
    public function create(Request $request)
    {
        $track = Track::findOrFail($request->track_id);

        return view('track_layouts.form', [
            'layout' => new TrackLayout(),
            'track' => $track,
            'mode' => 'create'
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'track_id' => 'required|exists:tracks,id',
            'name' => 'required|string|max:255',
            'length_km' => 'nullable|numeric',
            'active_from' => 'nullable|integer',
            'active_to' => 'nullable|integer',
        ]);

        TrackLayout::create($validated);

        return redirect()->route('tracks.edit', $validated['track_id'])
            ->with('success', 'Layout created.');
    }

    public function edit(TrackLayout $track_layout)
    {
        return view('track_layouts.form', [
            'layout' => $track_layout,
            'track' => $track_layout->track,
            'mode' => 'edit'
        ]);
    }

    public function update(Request $request, TrackLayout $track_layout)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'length_km' => 'nullable|numeric',
            'active_from' => 'nullable|integer',
            'active_to' => 'nullable|integer',
        ]);

        $track_layout->update($validated);

        return redirect()->route('tracks.edit', $track_layout->track_id)
            ->with('success', 'Layout updated.');
    }

    public function destroy(TrackLayout $track_layout)
    {
        $trackId = $track_layout->track_id;

        $track_layout->delete();

        return redirect()->route('tracks.edit', $trackId)
            ->with('success', 'Layout deleted.');
    }
}
