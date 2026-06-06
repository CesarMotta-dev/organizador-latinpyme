<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$email = 'mlopez@latinpymes.com';

// Buscar o crear al usuario mlopez@latinpymes.com
$user = \App\Models\User::firstOrCreate(
    ['email' => $email],
    [
        'name' => 'Mario Lopez',
        'password' => \Illuminate\Support\Facades\Hash::make('password123'), // Contraseña temporal si se crea nuevo
    ]
);

// Asegurar que tenga el rol de admin
$user->role = 'admin';
$user->save();

echo "Usuario {$user->email} actualizado a rol: {$user->role}\n";
