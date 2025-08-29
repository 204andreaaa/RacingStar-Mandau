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
        Schema::create('activity_results', function (Blueprint $table) {
            $table->id();

            // simple aja dulu: belum pakai foreign key constraint
            $table->unsignedBigInteger('user_id');      // id user pengisi
            $table->unsignedBigInteger('activity_id');  // id aktivitas
            $table->unsignedBigInteger('id_segmen');  // id aktivitas

            $table->dateTime('submitted_at');           // waktu submit
            $table->string('status')->default('done');  // done/skipped (string dulu)
            $table->string('before_photo')->nullable();             // path foto before
            $table->string('after_photo')->nullable();              // path foto after
            $table->unsignedInteger('point_earned')->default(0); // snapshot poin
            $table->text('note')->nullable();           // catatan opsional

            $table->foreign('id_segmen')->references('id_segmen')->on('segmens')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_results');
    }
};
