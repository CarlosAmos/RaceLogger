<?php

namespace App\Http\Controllers;

use App\Models\World;
use App\Models\Country;
use Illuminate\Http\Request;

class ConstructorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(World $world)
    {
        $constructors = $world->constructors()
            ->orderBy('name')
            ->get();

        return view('constructors.index', compact('world', 'constructors'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(World $world)
    {
        $countries = Country::orderBy('name')->get();

        return view('constructors.create', compact('world', 'countries'));

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country_id' => 'nullable|exists:countries,id',
            'color' => 'nullable|string|max:7',
        ]);

        $world->constructors()->create($validated);

        return redirect()
            ->route('worlds.constructors.index', $world)
            ->with('success', 'Team created successfully.');
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
