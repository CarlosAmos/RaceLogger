<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Driver;

class DriverController extends Controller
{
    public function index()
    {
        $drivers = Driver::with('country')->orderBy('last_name')->paginate(20);
        return view('drivers.index', compact('drivers'));
    }

    public function create()
    {
        return view('drivers.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'date_of_birth' => 'nullable|date',
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
        return view('drivers.edit', compact('driver'));
    }

    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'first_name'    => 'required|max:255',
            'last_name'     => 'required|max:255',
            'date_of_birth' => 'nullable|date',
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

