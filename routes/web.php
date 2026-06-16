<?php

// 1´©ÅÔâú IMPORTACIONES ORDENADAS (Siempre arriba del todo)
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\EmailRuleController;
use App\Http\Controllers\CompanyEmailController;
use App\Http\Controllers\GoogleController; // ­ƒæê Agregamos este para que no te tire error con Google
use App\Http\Controllers\DeletedEmailController;
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
    Route::match(['put', 'patch'], '/email-rules/{rule}', [EmailRuleController::class, 'update'])->name('email-rules.update');

    // Eliminar todas las reglas
    Route::delete('/email-rules/delete-all', [EmailRuleController::class, 'destroyAll'])->name('email-rules.destroyAll');

    // Eliminar varias reglas seleccionadas
    Route::delete('/email-rules/delete-multiple', [EmailRuleController::class, 'destroyMultiple'])->name('email-rules.destroyMultiple');

    // Eliminar una regla específica
    Route::delete('/email-rules/{rule}', [EmailRuleController::class, 'destroy'])->name('email-rules.destroy');

    // Ô£¿ NUEVO: Gesti├│n de Correos Empresariales
    Route::resource('company-emails', CompanyEmailController::class);
    Route::get('/api/company-emails/select', [CompanyEmailController::class, 'getForSelect']);
    Route::get('/deleted-emails', [DeletedEmailController::class, 'index'])->name('deleted-emails.index');

    // Rutas del perfil de usuario (Por defecto de Laravel Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// 🚀 5️⃣ ENDPOINT EXCLUSIVO PARA n8n (AFUERA DEL MIDDLEWARE DE AUTENTICACIÓN)
// Al estar afuera, n8n puede enviarle el backup sin que Laravel le pida iniciar sesión.
Route::post('/api/deleted-emails', [DeletedEmailController::class, 'store']);

// 6️⃣ CARGA DE RUTAS NATIVAS DE AUTENTICACIÓN (Login, Registro, Recuperar clave)
require __DIR__ . '/auth.php';
