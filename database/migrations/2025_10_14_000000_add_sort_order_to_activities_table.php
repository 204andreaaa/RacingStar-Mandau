<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('team_id');
            $table->index(['team_id', 'sort_order'], 'activities_team_sort_idx');
        });

        // Backfill urutan per-team, urut berdasarkan id ASC
        $teams = DB::table('activities')->select('team_id')->distinct()->pluck('team_id');
        foreach ($teams as $teamId) {
            $i = 1;
            DB::table('activities')
                ->where('team_id', $teamId)
                ->orderBy('id')
                ->chunkById(500, function ($rows) use (&$i) {
                    foreach ($rows as $r) {
                        DB::table('activities')->where('id', $r->id)->update(['sort_order' => $i++]);
                    }
                }, 'id');
        }
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropIndex('activities_team_sort_idx');
            $table->dropColumn('sort_order');
        });
    }
};
