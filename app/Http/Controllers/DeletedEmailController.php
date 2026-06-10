<?php

namespace App\Http\Controllers;

use App\Models\DeletedEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DeletedEmailController extends Controller
{
    /**
     * Muestra la vista en tu Dashboard (Blindado para producción)
     */
    public function index()
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        $query = DeletedEmail::query();

        // 🛠️ SOLUCIÓN AL ROJO: Cambiamos el método isAdmin() por la columna de la BD.
        // Si en tu tabla de usuarios la columna se llama diferente (ej: 'role'), puedes cambiarlo aquí.
        $esAdmin = isset($user->is_admin) && $user->is_admin == true;

        if (!$esAdmin) {
            // Si es un Trabajador normal, solo ve los correos que él mismo eliminó
            $query->where('user_id', $user->id);
        }

        // Paginamos usando la fecha de tu migración
        $deletedEmails = $query->latest('fecha_eliminacion')->paginate(20);

        return Inertia::render('DeletedEmails/Index', [
            'deletedEmails' => $deletedEmails
        ]);
    }

    /**
     * Recibe la petición POST segura desde n8n
     */
    public function store(Request $request)
    {
        $deletedEmail = DeletedEmail::create([
            'user_id'          => null,
            'correo_remitente' => $request->correo,
            'asunto'           => $request->asunto,
            'id_mensaje'       => $request->id_mensaje,
            'fecha_eliminacion'=> now(),
        ]);

        return response()->json([
            'message' => 'Backup de correo eliminado registrado con éxito',
            'data' => $deletedEmail
        ], 201);
    }
}
