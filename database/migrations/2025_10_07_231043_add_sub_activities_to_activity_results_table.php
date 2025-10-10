<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jalan hanya jika tabel ada & kolom belum ada
        if (Schema::hasTable('activity_results') &&
            ! Schema::hasColumn('activity_results', 'sub_activities')) {

            Schema::table('activity_results', function (Blueprint $t) {
                $t->json('sub_activities')->nullable();
            });
        }
    }

    public function down(): void
    {
        // drop hanya jika tabel & kolomnya ada
        if (Schema::hasTable('activity_results') &&
            Schema::hasColumn('activity_results', 'sub_activities')) {

            Schema::table('activity_results', function (Blueprint $t) {$t->dropColumn('sub_activities'); });
        }
    }
};
