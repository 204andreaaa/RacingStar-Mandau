<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void {
        Schema::table('activity_results', function (Blueprint $t) {
        $t->unsignedBigInteger('checklist_id')->after('id');
        });
    }
    public function down(): void {
        Schema::table('activity_results', function (Blueprint $t) {
        $t->dropColumn('checklist_id');
        });
    }
};
