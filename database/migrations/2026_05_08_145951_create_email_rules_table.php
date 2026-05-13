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
        Schema::create('email_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // El dueño de la regla
            $table->string('correo'); // El correo del cliente (ej: pagos@siigo.com)
            $table->string('carpeta'); // La carpeta destino (ej: PSE)
            $table->string('estado')->default('Activo'); // Activo o Inactivo
            $table->timestamps();
        });
    }    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_rules');
    }
};
