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
        Schema::create('activity_result_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('activity_result_id'); // FK ke activity_results.id
            $table->enum('kind', ['before','after']);         // jenis foto
            $table->string('path');                            // path relatif disk 'public'
            $table->timestamps();

            $table->foreign('activity_result_id')->references('id')->on('activity_results')->onDelete('cascade');
            $table->index(['activity_result_id','kind']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_result_photos');
    }
};
