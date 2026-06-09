<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\N8nIntegrationController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Endpoints limpios para n8n (Sin el prefijo /api porque ya está implícito)
Route::get('/email-rules', [N8nIntegrationController::class, 'getRules']);
Route::get('/email-rules/{companyEmailId}', [N8nIntegrationController::class, 'getRules']);
Route::post('/email-rules/sync', [N8nIntegrationController::class, 'updateRuleFromGemini']);
Route::post('/deleted-emails', [N8nIntegrationController::class, 'logDeletedEmail']);
Route::post('/correos-pendientes', [N8nIntegrationController::class, 'logUnclassifiedEmail']);

