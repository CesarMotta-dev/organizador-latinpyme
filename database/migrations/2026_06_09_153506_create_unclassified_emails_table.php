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
    Schema::create('unclassified_emails', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('company_email_id')->nullable(); // Cuenta corporativa (recepcion@, etc.)
        $table->string('correo_remitente'); // Quien envía el correo
        $table->string('asunto')->nullable(); // El asunto del correo
        $table->string('id_mensaje')->nullable(); // El ID único de Gmail por si necesitas consultarlo o moverlo luego
        $table->timestamps();

        // Si usas llaves foráneas:
        $table->foreign('company_email_id')->references('id')->on('company_emails')->onDelete('set null');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unclassified_emails');
    }
};
