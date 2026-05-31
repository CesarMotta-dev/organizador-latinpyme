<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_rules', function (Blueprint $table) {
            $table->foreignId('company_email_id')
                ->nullable()
                ->after('user_id')
                ->constrained('company_emails')
                ->onDelete('cascade');

            $table->string('tipo_filtro')
                ->nullable()
                ->after('estado')
                ->comment('Tipo: remitente, asunto, contenido');

            $table->text('valor_filtro')
                ->nullable()
                ->after('tipo_filtro')
                ->comment('Valor para comparar en el filtro');

            $table->index('company_email_id');
        });
    }

    public function down(): void
    {
        Schema::table('email_rules', function (Blueprint $table) {
            $table->dropForeignIdFor('CompanyEmail');
            $table->dropColumn(['company_email_id', 'tipo_filtro', 'valor_filtro']);
        });
    }
};
