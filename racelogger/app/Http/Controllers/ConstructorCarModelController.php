<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Constructor;
use App\Models\CarModel;
use App\Models\World;
use Inertia\Inertia;

class ConstructorCarModelController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function index(World $world, Constructor $constructor)
    {
        $carModels = $constructor->carModels()
            ->orderBy('year', 'desc')
            ->orderBy('name')
            ->get();

        return Inertia::render('car-models/index', compact('world', 'constructor', 'carModels'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(World $world, Constructor $constructor)
    {
        $engines = $world->engines()
            ->with('manufacturer')
            ->orderBy('name')
            ->get();

        return Inertia::render('car-models/create', compact('world', 'constructor', 'engines'));
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, World $world, Constructor $constructor)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'year' => 'nullable|integer|min:1950|max:' . now()->year,
            'engine_id' => 'nullable|exists:engines,id',
        ]);

        $constructor->carModels()->create($validated);

        return redirect()
            ->route('worlds.constructors.car-models.index', [$world, $constructor])
            ->with('success', 'Car model created successfully.');
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
