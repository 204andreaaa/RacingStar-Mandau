<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            // none = tidak dibatasi
            if (!Schema::hasColumn('activities', 'limit_period')) {
                $t->enum('limit_period', ['none','daily','weekly','monthly'])
                  ->default('none')
                  ->after('is_active');
            }
            if (!Schema::hasColumn('activities', 'limit_quota')) {
                $t->unsignedSmallInteger('limit_quota')
                  ->default(1)
                  ->after('limit_period');
            }
        });
    }

    public function down(): void
    {
        Schema::table('activities', function (Blueprint $t) {
            if (Schema::hasColumn('activities', 'limit_quota')) $t->dropColumn('limit_quota');
            if (Schema::hasColumn('activities', 'limit_period')) $t->dropColumn('limit_period');
        });
    }
};
