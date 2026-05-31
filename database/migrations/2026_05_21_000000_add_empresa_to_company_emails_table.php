<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_emails', function (Blueprint $table) {
            $table->string('empresa')->after('user_id')->nullable();
            $table->index('empresa');
        });
    }

    public function down(): void
    {
        Schema::table('company_emails', function (Blueprint $table) {
            $table->dropIndex(['empresa']);
            $table->dropColumn('empresa');
        });
    }
};
