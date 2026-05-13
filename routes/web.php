<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Http\Request;
use App\Models\EmailRule; // Importamos nuestro nuevo modelo

Route::get('/', function () {
    return redirect()->route('login');
});

// 1️⃣ RUTA DEL DASHBOARD (Modificada para enviar los datos reales)
Route::get('/dashboard', function () {
    // Buscamos las reglas del usuario que inició sesión, ordenadas por la más reciente
    $misReglas = auth()->user()->emailRules()->latest()->get();

    return Inertia::render('Dashboard', [
        'reglasDB' => $misReglas // Se las enviamos a Vue con el nombre 'reglasDB'
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

// 2️⃣ RUTA PARA GUARDAR LA NUEVA REGLA
Route::post('/email-rules', function (Request $request) {
    // Validamos que los datos vengan correctos
    $request->validate([
        'correo' => 'required|email',
        'carpeta' => 'required|string|max:255',
        'asunto' => 'nullable|string|max:255',
        'observaciones' => 'nullable|string|max:1000',
        'carpeta_sugerida' => 'nullable|string|max:255',
        'confirma_sugerencia' => 'nullable|string|max:50',
        'carpeta_elegida' => 'nullable|string|max:255',
        'id_mensaje' => 'nullable|string|max:255',
    ]);

    // Guardamos en la base de datos a nombre de este usuario
    auth()->user()->emailRules()->create([
        'correo' => $request->correo,
        'carpeta' => $request->carpeta,
        'asunto' => $request->asunto,
        'observaciones' => $request->observaciones,
        'carpeta_sugerida' => $request->carpeta_sugerida,
        'confirma_sugerencia' => $request->confirma_sugerencia,
        'carpeta_elegida' => $request->carpeta_elegida,
        'id_mensaje' => $request->id_mensaje,
        'estado' => 'Activo',
    ]);

    // Recargamos la página (Inertia hará esto sin parpadear la pantalla)
    return back();
})->middleware('auth')->name('email-rules.store');

// 2.1️⃣ RUTA PARA IMPORTAR REGLAS DESDE UN SHEET
Route::post('/email-rules/import', function (Request $request) {
    $request->validate([
        'csv' => 'required|string',
    ]);

    $csv = trim($request->csv);
    $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $csv)));

    if (empty($lines)) {
        return back()->with('error', 'CSV vacío');
    }

    $headers = str_getcsv(array_shift($lines));
    $headers = array_map(fn ($header) => strtoupper(trim($header)), $headers);

    $map = [
        'CORREOS' => 'correo',
        'CORREO' => 'correo',
        'CARPETA' => 'carpeta',
        'ASUNTO' => 'asunto',
        'OBSERVACIONES' => 'observaciones',
        'OBSERVACION' => 'observaciones',
        'CARPETA SUGERIDA' => 'carpeta_sugerida',
        'CARPETA_SUGERIDA' => 'carpeta_sugerida',
        'CONFIRMA SUGERENCIA' => 'confirma_sugerencia',
        'CONFIRMA_SUGERENCIA' => 'confirma_sugerencia',
        'CARPETA ELEGIDA' => 'carpeta_elegida',
        'CARPETA_ELEGIDA' => 'carpeta_elegida',
        'ID_MENSAJE' => 'id_mensaje',
        'ID MENSAJE' => 'id_mensaje',
    ];

    foreach ($lines as $line) {
        $row = str_getcsv($line);
        $data = [];

        foreach ($headers as $index => $header) {
            if (!isset($row[$index])) {
                continue;
            }
            $key = $map[$header] ?? null;
            if ($key) {
                $data[$key] = trim($row[$index]);
            }
        }

        if (empty($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
            continue;
        }

        auth()->user()->emailRules()->updateOrCreate(
            ['correo' => $data['correo']],
            array_merge($data, ['estado' => 'Activo'])
        );
    }

    return back();
})->middleware('auth')->name('email-rules.import');

// 3️⃣ RUTA PARA ACTUALIZAR UNA REGLA EXISTENTE
Route::patch('/email-rules/{rule}', function (Request $request, EmailRule $rule) {
    $request->validate([
        'correo' => 'required|email',
        'carpeta' => 'required|string|max:255',
        'asunto' => 'nullable|string|max:255',
        'observaciones' => 'nullable|string|max:1000',
        'carpeta_sugerida' => 'nullable|string|max:255',
        'confirma_sugerencia' => 'nullable|string|max:50',
        'carpeta_elegida' => 'nullable|string|max:255',
        'id_mensaje' => 'nullable|string|max:255',
    ]);

    // Aseguramos que el usuario solo pueda actualizar sus propias reglas
    abort_if($rule->user_id !== auth()->id(), 403);

    $rule->update([
        'correo' => $request->correo,
        'carpeta' => $request->carpeta,
        'asunto' => $request->asunto,
        'observaciones' => $request->observaciones,
        'carpeta_sugerida' => $request->carpeta_sugerida,
        'confirma_sugerencia' => $request->confirma_sugerencia,
        'carpeta_elegida' => $request->carpeta_elegida,
        'id_mensaje' => $request->id_mensaje,
    ]);

    return back();
})->middleware('auth')->name('email-rules.update');

// Rutas del perfil (Por defecto de Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';