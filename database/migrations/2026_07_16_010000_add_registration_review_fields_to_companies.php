<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('registration_status', 20)->default('approved')->after('status')->index();
            $table->timestamp('registration_reviewed_at')->nullable()->after('registration_status');
            $table->foreignId('registration_reviewed_by')->nullable()->after('registration_reviewed_at')
                ->constrained('users')->nullOnDelete();
            $table->text('registration_review_reason')->nullable()->after('registration_reviewed_by');
        });

        DB::table('companies')
            ->where('status', false)
            ->whereNull('cnpj_verificado_em')
            ->whereNotNull('email_administrador')
            ->update(['registration_status' => 'pending']);
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['registration_reviewed_by']);
            $table->dropIndex(['registration_status']);
            $table->dropColumn([
                'registration_status',
                'registration_reviewed_at',
                'registration_reviewed_by',
                'registration_review_reason',
            ]);
        });
    }
};
