<?php

// database/migrations/2025_08_14_000999_add_remember_token_to_user_bestrising.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void {
    Schema::table('user_bestrising', function (Blueprint $table) {
      if (!Schema::hasColumn('user_bestrising', 'remember_token')) {
        $table->rememberToken()->after('password');
      }
    });
  }
  public function down(): void {
    Schema::table('user_bestrising', function (Blueprint $table) {
      $table->dropColumn('remember_token');
    });
  }
};
