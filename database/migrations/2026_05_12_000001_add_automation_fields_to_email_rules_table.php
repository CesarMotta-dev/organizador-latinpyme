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
        Schema::table('email_rules', function (Blueprint $table) {
            $table->string('asunto')->nullable()->after('carpeta');
            $table->text('observaciones')->nullable()->after('asunto');
            $table->string('carpeta_sugerida')->nullable()->after('observaciones');
            $table->string('confirma_sugerencia')->nullable()->after('carpeta_sugerida');
            $table->string('carpeta_elegida')->nullable()->after('confirma_sugerencia');
            $table->string('id_mensaje')->nullable()->after('carpeta_elegida');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('email_rules', function (Blueprint $table) {
            $table->dropColumn([
                'asunto',
                'observaciones',
                'carpeta_sugerida',
                'confirma_sugerencia',
                'carpeta_elegida',
                'id_mensaje',
            ]);
        });
    }
};
