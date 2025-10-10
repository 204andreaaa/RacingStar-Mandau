<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // jalan hanya jika tabel ada & kolom belum ada
        if (Schema::hasTable('activities') &&
            ! Schema::hasColumn('activities', 'sub_activities')) {

            Schema::table('activities', function (Blueprint $t) {
                $t->json('sub_activities')->nullable();
            });
        }
    }

    public function down(): void
    {
        // drop hanya jika tabel & kolomnya ada
        if (Schema::hasTable('activities') &&
            Schema::hasColumn('activities', 'sub_activities')) {

            Schema::table('activities', function (Blueprint $t) {$t->dropColumn('sub_activities'); });
        }
    }
};
