<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activity_results', function (Blueprint $table) {
            if (!Schema::hasColumn('activity_results', 'is_rejected')) {
                $table->boolean('is_rejected')->default(false)->after('is_approval');
            }

            if (!Schema::hasColumn('activity_results', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('activity_results', function (Blueprint $table) {
            if (Schema::hasColumn('activity_results', 'is_rejected')) {
                $table->dropColumn('is_rejected');
            }
            if (Schema::hasColumn('activity_results', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};
