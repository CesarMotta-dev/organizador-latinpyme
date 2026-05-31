<?php

// 1´©ÅÔâú IMPORTACIONES ORDENADAS (Siempre arriba del todo)
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailRuleController;
use App\Http\Controllers\CompanyEmailController;
use App\Http\Controllers\GoogleController; // ­ƒæê Agregamos este para que no te tire error con Google
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Models\EmailRule;
use Inertia\Inertia;

// 2´©ÅÔâú RUTAS DE AUTENTICACI├ôN CON GOOGLE (OAuth)
Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);
Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

// 3´©ÅÔâú RUTA RA├ìZ (Muestra p├ígina de bienvenida con acceso al login/registro)
Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

// 4´©ÅÔâú RUTAS PROTEGIDAS PARA EL ORGANIZADOR DE EMAILS (Requieren inicio de sesi├│n)
Route::middleware(['auth', 'verified'])->group(function () {

    // Vista principal del Dashboard (Carga las reglas reales en la interfaz)
    Route::get('/dashboard', [EmailRuleController::class, 'index'])->name('dashboard');

    // Guardar una nueva regla creada a mano desde el formulario
    Route::post('/email-rules', [EmailRuleController::class, 'store'])->name('email-rules.store');

    // Importar reglas de automatizaci├│n directamente desde Google Sheets
    Route::post('/email-rules/import', [EmailRuleController::class, 'import'])->name('email-rules.import');

    // Actualizar/Modificar una regla existente (Filtros, carpetas, etc.)
    Route::patch('/email-rules/{rule}', [EmailRuleController::class, 'update'])->name('email-rules.update');

    // Ô£¿ NUEVO: Gesti├│n de Correos Empresariales
    Route::resource('company-emails', CompanyEmailController::class);
    Route::get('/api/company-emails/select', [CompanyEmailController::class, 'getForSelect']);

    // Rutas del perfil de usuario (Por defecto de Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 5´©ÅÔâú CARGA DE RUTAS NATIVAS DE AUTENTICACI├ôN (Login, Registro, Recuperar clave)
require __DIR__.'/auth.php';
