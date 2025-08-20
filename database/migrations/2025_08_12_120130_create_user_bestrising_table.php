<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_bestrising', function (Blueprint $table) {
            $table->id('id_userBestrising');
            $table->string('nik');
            $table->string('nama');
            $table->string('email')->unique();
            $table->string('password');

            $table->unsignedBigInteger('kategori_user_id');
            $table->foreign('kategori_user_id')
                ->references('id_kategoriuser')->on('kategoriuser')
                ->onDelete('cascade');

            $table->unsignedBigInteger('id_region')->nullable();
            $table->foreign('id_region')
                ->references('id_region')->on('regions')
                ->onDelete('cascade');

            $table->unsignedBigInteger('id_serpo')->nullable();
            $table->foreign('id_serpo')
                ->references('id_serpo')->on('serpos')
                ->onDelete('cascade');

            // ❌ tidak ada id_segmen di sini, karena pindah ke pivot
            $table->timestamps();

            $table->unique(['nik']); // bagusnya unik juga
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_bestrising');
    }
};
