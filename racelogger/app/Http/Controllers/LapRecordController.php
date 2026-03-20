<?php

namespace App\Http\Controllers;

use App\Services\RecordComputeService;

class LapRecordController extends Controller
{
    public function index(RecordComputeService $service)
    {
        $worldId = session('active_world_id');
        $records = $service->compute($worldId);

        return view('lap-records.index', compact('records'));
    }
}
