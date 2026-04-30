<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\World;
use App\Models\Country;
use Inertia\Inertia;

class EntrantController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request, World $world)
    {
        $countries = Country::orderBy('name')->get();

        $defaultName = $request->query('name', '');

        $defaultCountryId = null;
        if ($request->has('acc_nationality')) {
            $country = Country::where('acc_id', (int) $request->query('acc_nationality'))->first();
            $defaultCountryId = $country?->id;
        }

        return Inertia::render('entrants/create', compact('world', 'countries', 'defaultName', 'defaultCountryId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
        ]);

        $world->entrants()->create($validated);

        return redirect()
            ->route('worlds.constructors.index', $world)
            ->with('success', 'Entrant created successfully.');
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
