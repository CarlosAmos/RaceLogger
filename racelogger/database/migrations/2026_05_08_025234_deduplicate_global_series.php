<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop the (series_id, year) unique — after merging series across worlds,
        // two worlds can both have e.g. F1 2024 which would violate this constraint.
        // A new (world_id, series_id, year) unique will be added when world_id moves to seasons.
        Schema::table('seasons', function (Blueprint $table) {
            $table->dropUnique('seasons_series_id_year_unique');
        });

        // Find all short_names that appear more than once (skip blank/null)
        $groups = DB::table('series')
            ->select('short_name', DB::raw('MIN(id) as canonical_id'), DB::raw('GROUP_CONCAT(id ORDER BY id) as all_ids'))
            ->whereNotNull('short_name')
            ->where('short_name', '!=', '')
            ->groupBy('short_name')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $canonicalId  = (int) $group->canonical_id;
            $duplicateIds = array_filter(
                explode(',', $group->all_ids),
                fn ($id) => (int) $id !== $canonicalId
            );
            $duplicateIds = array_map('intval', array_values($duplicateIds));

            DB::table('seasons')
                ->whereIn('series_id', $duplicateIds)
                ->update(['series_id' => $canonicalId]);

            DB::table('season_entries')
                ->whereIn('series_id', $duplicateIds)
                ->update(['series_id' => $canonicalId]);

            // entries table is currently empty but repoint for correctness
            DB::table('entries')
                ->whereIn('series_id', $duplicateIds)
                ->update(['series_id' => $canonicalId]);

            DB::table('series')->whereIn('id', $duplicateIds)->delete();
        }
    }

    public function down(): void
    {
        // Irreversible — duplicate series rows cannot be restored
    }
};
