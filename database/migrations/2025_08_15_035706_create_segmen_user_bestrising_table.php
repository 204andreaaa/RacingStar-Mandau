<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('segmen_user_bestrising')) {
            Schema::create('segmen_user_bestrising', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('id_userBestrising');
                $table->unsignedBigInteger('id_segmen');
                $table->timestamps();

                $table->foreign('id_userBestrising')
                    ->references('id_userBestrising')->on('user_bestrising')
                    ->onDelete('cascade');

                $table->foreign('id_segmen')
                    ->references('id_segmen')->on('segmens')
                    ->onDelete('cascade');

                $table->unique(['id_userBestrising', 'id_segmen']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('segmen_user_bestrising');
    }
};
