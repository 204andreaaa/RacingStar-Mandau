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
        Schema::create('serpos', function (Blueprint $table) {
            $table->id('id_serpo');
            $table->unsignedBigInteger('id_region'); // FK ke regions
            $table->string('nama_serpo', 100);
            $table->timestamps();

            $table->foreign('id_region')
                ->references('id_region')->on('regions')
                ->onDelete('cascade'); // hapus serpo jika region dihapus
            $table->unique(['id_region','nama_serpo']); // optional: unik per region
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('serpos');
    }
};
