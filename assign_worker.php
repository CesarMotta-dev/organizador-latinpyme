<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = \App\Models\CompanyEmail::find(1);
if ($c) {
    $c->worker_id = 3;
    $c->save();
    echo "Trabajador asignado exitosamente.\n";
}
