<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('checklists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');      // users.id
            $table->string('team', 20);                 // 'SERPO' / 'NOC' / etc
            $table->unsignedBigInteger('id_region');    // regions.id_region
            $table->unsignedBigInteger('id_serpo')->nullable();     // serpos.id_serpo
            $table->unsignedBigInteger('id_segmen')->nullable();    // segmens.id_segmen

            $table->dateTime('started_at')->nullable();
            $table->dateTime('submitted_at')->nullable();
            $table->string('status', 20)->default('pending'); // pending/review/completed
            $table->unsignedInteger('total_point')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checklists');
    }
};
