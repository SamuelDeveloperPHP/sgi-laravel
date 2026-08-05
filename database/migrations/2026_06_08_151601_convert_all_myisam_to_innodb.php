<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        // Encontra todas as tabelas no banco de dados atual que utilizam a engine MyISAM
        $tables = DB::select("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND ENGINE = 'MyISAM'");
        
        foreach ($tables as $table) {
            $tableName = $table->TABLE_NAME;
            // Converte a tabela para InnoDB
            DB::statement("ALTER TABLE `{$tableName}` ENGINE=InnoDB");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Como estamos fazendo um upgrade estrutural benéfico global, 
        // a reversão para MyISAM não é recomendada ou estritamente necessária aqui.
    }
};
