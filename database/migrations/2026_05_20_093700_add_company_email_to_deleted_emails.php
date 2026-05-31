<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deleted_emails', function (Blueprint $table) {
            $table->foreignId('company_email_id')
                ->nullable()
                ->after('user_id')
                ->constrained('company_emails')
                ->onDelete('set null');

            $table->index('company_email_id');
        });
    }

    public function down(): void
    {
        Schema::table('deleted_emails', function (Blueprint $table) {
            $table->dropForeignIdFor('CompanyEmail');
            $table->dropColumn('company_email_id');
        });
    }
};
