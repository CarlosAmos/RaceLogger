<?php

namespace App\Http\Controllers;

use App\Services\RecordComputeService;
use Inertia\Inertia;

class LapRecordController extends Controller
{
    public function index(RecordComputeService $service)
    {
        $worldId = session('active_world_id');
        $records = $service->compute($worldId);

        return Inertia::render('lap-records/index', compact('records'));
    }
}
