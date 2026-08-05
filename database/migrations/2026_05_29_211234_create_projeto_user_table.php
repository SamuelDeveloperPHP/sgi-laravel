<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sts_projetos')) {
            return;
        }

        Schema::create('projeto_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('projeto_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->foreign('projeto_id')->references('id')->on('sts_projetos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            
            $table->unique(['projeto_id', 'user_id']); // impede usuário duplicado no mesmo projeto
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projeto_user');
    }
};
