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
        Schema::table('sts_nossa_historia', function (Blueprint $table) {
            $table->foreignId('user_create')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_edit')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_nossa_historia', function (Blueprint $table) {
            $table->dropForeign(['user_create']);
            $table->dropForeign(['user_edit']);
            $table->dropColumn(['user_create', 'user_edit']);
        });
    }
};
