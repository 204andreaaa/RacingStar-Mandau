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
        Schema::create('activities', function (Blueprint $tabel) {
            $tabel->id();
            $tabel->unsignedSmallInteger('team_id')->default(1)->index(); // ⟵ tambahin disini
            $tabel->string('name');
            $tabel->text('description')->nullable();
            $tabel->unsignedInteger('point')->default(0);
            $tabel->boolean('is_active')->default(true);
            $tabel->boolean('is_checked_segmen')->default(false);
            $tabel->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
