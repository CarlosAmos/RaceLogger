<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\World;
use App\Models\WorldRecord;
use App\Services\RecordComputeService;
use Carbon\Carbon;

class RecordsComputeCommand extends Command
{
    protected $signature = 'records:compute {world_id? : ID of the world to compute (omit to compute all worlds)}';
    protected $description = 'Pre-compute driver records and store as JSON in world_records';

    public function handle(RecordComputeService $service): int
    {
        $worldIdArg = $this->argument('world_id');
        $worlds = $worldIdArg
            ? World::where('id', (int) $worldIdArg)->get()
            : World::all();

        if ($worlds->isEmpty()) {
            $this->error('No worlds found.');
            return 1;
        }

        foreach ($worlds as $world) {
            $this->info("Computing records for: {$world->name} (id={$world->id})...");

            $data = $service->compute($world->id);

            WorldRecord::updateOrCreate(
                ['world_id' => $world->id],
                [
                    'data'        => json_encode($data),
                    'computed_at' => Carbon::now(),
                ]
            );

            $this->info("  Done.");
        }

        $this->info("\nAll records computed.");
        return 0;
    }
}
