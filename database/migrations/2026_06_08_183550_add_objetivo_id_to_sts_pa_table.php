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
        if (!Schema::hasColumn('sts_pa', 'objetivo_qualidade_id')) {
            Schema::table('sts_pa', function (Blueprint $table) {
                $table->unsignedBigInteger('objetivo_qualidade_id')->nullable()->after('id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sts_pa', function (Blueprint $table) {
            //
        });
    }
};
