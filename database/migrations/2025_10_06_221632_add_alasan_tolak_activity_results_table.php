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
            ! Schema::hasColumn('activity_results', 'alasan_tolak')) {

            Schema::table('activity_results', function (Blueprint $t) {
                $t->text('alasan_tolak')->nullable();
            });
        }
    }

    public function down(): void
    {
        // drop hanya jika tabel & kolomnya ada
        if (Schema::hasTable('activity_results') &&
            Schema::hasColumn('activity_results', 'alasan_tolak')) {

            Schema::table('activity_results', function (Blueprint $t) {$t->dropColumn('alasan_tolak'); });
        }
    }
};
