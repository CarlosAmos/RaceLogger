<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Series;
use App\Models\World;

class SeriesController extends Controller
{
    public function index()
    {
        $series = Series::with('world')->paginate(10);
        return view('series.index', compact('series'));
    }

    public function create()
    {
        $worlds = World::all();
        return view('series.create', compact('worlds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'name' => 'required|string|max:255',
            'is_multiclass' => 'boolean',
        ]);

        Series::create($validated);

        return redirect()->route('series.index')
            ->with('success', 'Series created.');
    }

    public function show(Series $series)
    {
        return view('series.show', compact('series'));
    }

    public function edit(Series $series)
    {
        $worlds = World::all();
        return view('series.edit', compact('series', 'worlds'));
    }

    public function update(Request $request, Series $series)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'name' => 'required|string|max:255',
            'is_multiclass' => 'boolean',
        ]);

        $series->update($validated);

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

