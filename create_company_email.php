<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::first();
if ($user) {
    echo "Actualizando rol de: " . $user->email . "\n";
    $user->update(['role' => 'admin']);
    
    // Cambiar la cuenta por la de mlopez@latinpymes.com
    $user->companyEmails()->firstOrCreate(
        ['email' => 'mlopez@latinpymes.com'],
        [
            'empresa' => 'Latinpymes',
            'nombre' => 'Administrador principal',
            'descripcion' => 'Cuenta del administrador de Google Workspace',
            'estado' => 'Activo'
        ]
    );
    echo "Cuenta empresarial mlopez@latinpymes.com verificada/creada exitosamente.\n";
} else {
    echo "No hay usuarios en la base de datos.\n";
}

