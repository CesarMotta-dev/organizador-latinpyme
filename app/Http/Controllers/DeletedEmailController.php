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

        $esAdmin = isset($user->is_admin) ? $user->is_admin : (method_exists($user, 'isAdmin') ? $user->isAdmin() : false);

        if (!$esAdmin) {
            // Si es un Trabajador, ve los correos eliminados de las cuentas a las que tiene acceso
            $validCompanyEmailIds = \App\Models\CompanyEmail::where(function ($q) use ($user) {
                $q->where('worker_id', $user->id)
                  ->orWhere('user_id', $user->id);
            })->pluck('id')->toArray();
            
            $query->whereIn('company_email_id', $validCompanyEmailIds);
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
        // Buscamos la cuenta corporativa a partir del parámetro company_email
        $companyEmail = null;
        if ($request->filled('company_email')) {
            $companyEmail = \App\Models\CompanyEmail::where('email', trim($request->input('company_email')))->first();
        }

        // Si se encuentra, usamos el ID de su dueño, de lo contrario lo dejamos en 1 o null
        $userId = $companyEmail ? $companyEmail->user_id : (\App\Models\CompanyEmail::value('user_id') ?? 1);
        $companyEmailId = $companyEmail ? $companyEmail->id : (\App\Models\CompanyEmail::value('id') ?? 1);

        $deletedEmail = DeletedEmail::create([
            'user_id'          => $userId,
            'company_email_id' => $companyEmailId,
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
