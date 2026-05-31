<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('email_rules', function (Blueprint $table) {
            if (!Schema::hasColumn('email_rules', 'correo')) {
                $table->string('correo')->nullable()->after('company_email_id');
            }
        });

        if (Schema::hasColumn('email_rules', 'correos')) {
            DB::table('email_rules')
                ->whereNull('correo')
                ->update(['correo' => DB::raw('correos')]);
        }
    }

    public function down(): void
    {
        Schema::table('email_rules', function (Blueprint $table) {
            if (Schema::hasColumn('email_rules', 'correo')) {
                $table->dropColumn('correo');
            }
        });
    }
};
