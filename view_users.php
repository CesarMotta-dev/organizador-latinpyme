<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

foreach (\App\Models\User::all() as $user) {
    echo "ID: {$user->id} | Name: {$user->name} | Email: {$user->email} | Role: {$user->role}\n";
}

$emails = \App\Models\CompanyEmail::all();
echo "\nCuentas Empresariales:\n";
foreach ($emails as $email) {
    echo "ID: {$email->id} | Email: {$email->email} | Worker_ID: {$email->worker_id} | User_ID: {$email->user_id}\n";
}
