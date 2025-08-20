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
        Schema::create('segmens', function (Blueprint $table) {
            $table->id('id_segmen');
            $table->unsignedBigInteger('id_serpo'); // FK ke serpos
            $table->string('nama_segmen', 100);
            $table->timestamps();

            $table->foreign('id_serpo')
                ->references('id_serpo')->on('serpos')
                ->onDelete('cascade'); // hapus segmen jika serpo dihapus
            $table->unique(['id_serpo','nama_segmen']); // optional: unik per serpo
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('segmens');
    }
};
