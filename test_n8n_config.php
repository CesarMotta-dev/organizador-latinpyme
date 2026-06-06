<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$url = config('services.n8n.webhook_url');
echo "URL en configuracion: " . $url . "\n";

$response = \Illuminate\Support\Facades\Http::post($url, ['evento' => 'test_desde_script']);
echo "Status: " . $response->status() . "\n";
echo "Body: " . $response->body() . "\n";
