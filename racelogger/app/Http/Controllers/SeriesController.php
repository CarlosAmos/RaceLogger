<?php

namespace App\Http\Controllers;

use App\Models\Series;
use Illuminate\Http\Request;

class SeriesController extends Controller
{
    public function index()
    {
        $worldId = session('active_world_id');

        $series = Series::where('world_id', $worldId)
            ->orderBy('created_at')
            ->get();

        return view('series.index', compact('series'));
    }

    public function create()
    {
        return view('series.create');
    }

    public function created(Series $series)
    {
        return view('series.created', compact('series'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $worldId = session('active_world_id');

        $series = Series::create([
            'world_id' => $worldId,
            'name' => $validated['name'],
            'is_multiclass' => $request->has('is_multiclass'),
        ]);

        return redirect()->route('series.created', $series);
    }


    public function show(Series $series)
    {
        return view('series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        return view('series.edit', compact('series'));
    }

    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'is_multiclass' => 'nullable|boolean',
        ]);

        $series->update([
            'name' => $validated['name'],
            'is_multiclass' => $request->has('is_multiclass'),
        ]);

        return redirect()->route('series.index')
            ->with('success', 'Series updated.');
    }

    public function destroy(Series $series)
    {
        $series->delete();

        return redirect()->route('series.index')
            ->with('success', 'Series deleted.');
    }
}


