<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\World;
use App\Models\Driver;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::with('world')->paginate(20);
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        $worlds = World::all();
        return view('drivers.create', compact('worlds'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'nationality' => 'nullable|max:255',
            'date_of_birth' => 'nullable|date',
            'rating' => 'nullable|integer',
        ]);

        Driver::create($validated);

        return redirect()->route('drivers.index')
            ->with('success', 'Driver created.');
    }

    public function show(Driver $driver)
    {
        return view('drivers.show', compact('driver'));
    }

    public function edit(Driver $driver)
    {
        $worlds = World::all();
        return view('drivers.edit', compact('driver', 'worlds'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'world_id' => 'required|exists:worlds,id',
            'first_name' => 'required|max:255',
            'last_name' => 'required|max:255',
            'nationality' => 'nullable|max:255',
            'date_of_birth' => 'nullable|date',
            'rating' => 'nullable|integer',
        ]);

        $driver->update($validated);

        return redirect()->route('drivers.index')
            ->with('success', 'Driver updated.');
    }

    public function destroy(Driver $driver)
    {
        $driver->delete();
        return redirect()->route('drivers.index')
            ->with('success', 'Driver deleted.');
    }
}

