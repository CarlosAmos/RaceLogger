<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Season;
use App\Models\SeasonEntry;
use App\Models\EntryClass;
use App\Models\EntryCar;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EntryCarController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(
        World $world,
        Season $season,
        SeasonEntry $seasonEntry,
        EntryClass $entryClass
    ) {
        $entryCars = $entryClass->entryCars()
            ->with('carModel.constructor')
            ->orderBy('car_number')
            ->get();

        return view('entry-cars.index', compact(
            'world',
            'season',
            'seasonEntry',
            'entryClass',
            'entryCars'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(
        World $world,
        Season $season,
        SeasonEntry $seasonEntry,
        EntryClass $entryClass
    ) {
        $carModels = $seasonEntry->constructor
            ->carModels()
            ->with('engine')
            ->orderBy('name')
            ->get();

        return view('entry-cars.create', compact(
            'world',
            'season',
            'seasonEntry',
            'entryClass',
            'carModels'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Request $request,
        World $world,
        Season $season,
        SeasonEntry $seasonEntry,
        EntryClass $entryClass
    ) {
        $validated = $request->validate([
            'car_number' => [
                'required',
                'string',
                Rule::unique('entry_cars')
                    ->where('entry_class_id', $entryClass->id),
            ],
            'car_model_id' => 'required|exists:car_models,id',
            'livery_name' => 'nullable|string|max:255',
            'chassis_code' => 'nullable|string|max:255',
        ]);

        $entryClass->entryCars()->create($validated);

        return redirect()
            ->route(
                'worlds.seasons.season-entries.entry-classes.entry-cars.index',
                [$world, $season, $seasonEntry, $entryClass]
            )
            ->with('success', 'Entry car created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
