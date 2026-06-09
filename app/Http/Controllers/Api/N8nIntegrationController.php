<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CompanyEmail;
use App\Models\EmailRule;
use App\Models\DeletedEmail;
use Illuminate\Http\Request;

class N8nIntegrationController extends Controller
{
    /**
     * Get rules for a specific company email (used by n8n).
     */
    public function getRules(Request $request, $companyEmailId = null)
    {
        if (!$companyEmailId && $request->filled('company_email')) {
            $companyEmailId = CompanyEmail::where('email', $request->company_email)->value('id');
        }

        if ($companyEmailId) {
            $rules = EmailRule::where('company_email_id', $companyEmailId)
                ->where('estado', 'Activo')
                ->with('companyEmail')
                ->get();

            return $this->rulesResponse($rules, $companyEmailId);
        }

        $rules = EmailRule::where('estado', 'Activo')
            ->with('companyEmail')
            ->get();

        return $this->rulesResponse($rules);
    }

    private function rulesResponse($rules, ?int $companyEmailId = null)
    {
        $usableRules = $rules
            ->filter(fn ($rule) => filled($rule->correo) && filled($rule->carpeta))
            ->values()
            ->map(fn ($rule) => [
                'id' => $rule->id,
                'company_email_id' => $rule->company_email_id,
                'cuenta' => $rule->companyEmail?->email,
                'correo' => $rule->correo,
                'correo_remitente' => $rule->correo,
                'carpeta' => $rule->carpeta,
                'carpeta_destino' => $rule->carpeta,
                'asunto' => $rule->asunto,
                'observaciones' => $rule->observaciones,
                'estado' => $rule->estado,
            ]);

        return response()->json([
            'success' => true,
            'company_email_id' => $companyEmailId,
            'total_reglas' => $rules->count(),
            'reglas_utilizables' => $usableRules->count(),
            'reglas_incompletas' => $rules->count() - $usableRules->count(),
            'data' => $usableRules,
            'reglas' => $usableRules,
        ]);
    }

    /**
     * n8n (nodo Gemini) llama este endpoint para actualizar una regla con su sugerencia.
     */
    public function updateRuleFromGemini(Request $request)
    {
        $request->validate([
            'company_email_id' => 'nullable|exists:company_emails,id',
            'correo' => 'required|email',
            'carpeta_sugerida' => 'nullable|string',
            'asunto' => 'nullable|string',
            'observaciones' => 'nullable|string',
            'confirma_sugerencia' => 'nullable|string',
            'carpeta_elegida' => 'nullable|string',
            'id_mensaje' => 'nullable|string',
        ]);

        $rule = EmailRule::where('correo', $request->correo)
            ->when($request->company_email_id, fn ($query) => $query->where('company_email_id', $request->company_email_id))
            ->first();

        if ($rule) {
            // Actualizamos
            $rule->update([
                'carpeta_sugerida' => $request->carpeta_sugerida ?? $rule->carpeta_sugerida,
                'asunto' => $request->asunto ?? $rule->asunto,
                'observaciones' => $request->observaciones ?? $rule->observaciones,
                'confirma_sugerencia' => $request->confirma_sugerencia ?? $rule->confirma_sugerencia,
                'carpeta_elegida' => $request->carpeta_elegida ?? $rule->carpeta_elegida,
                'id_mensaje' => $request->id_mensaje ?? $rule->id_mensaje,
            ]);
            $action = 'updated';
        } else {
            // Si el correo no tiene regla, la creamos con la sugerencia
            $companyEmailId = $request->company_email_id;

            $rule = EmailRule::create([
                'company_email_id' => $companyEmailId,
                'correo' => $request->correo,
                'carpeta' => $request->carpeta ?? 'INBOX',
                'carpeta_sugerida' => $request->carpeta_sugerida,
                'asunto' => $request->asunto,
                'observaciones' => $request->observaciones,
                'confirma_sugerencia' => $request->confirma_sugerencia,
                'carpeta_elegida' => $request->carpeta_elegida,
                'id_mensaje' => $request->id_mensaje,
                'estado' => 'Activo',
            ]);
            $action = 'created';
        }

        return response()->json([
            'success' => true,
            'action' => $action,
            'data' => $rule
        ]);
    }

    /**
     * n8n llama a este endpoint cuando elimina un correo para dejar registro (trazabilidad).
     */
    public function logDeletedEmail(Request $request)
    {
        $request->validate([
            'company_email_id' => 'nullable|exists:company_emails,id',
            'correo_remitente' => 'nullable|string',
            'asunto' => 'nullable|string',
            'id_mensaje' => 'nullable|string',
        ]);

        $deletedLog = \App\Models\DeletedEmail::create([
            'company_email_id' => $request->company_email_id,
            'correo_remitente' => $request->correo_remitente,
            'asunto' => $request->asunto,
            'id_mensaje' => $request->id_mensaje,
            'fecha_eliminacion' => now(),
        ]);

        return response()->json([
            'success' => true,
            'data' => $deletedLog
        ]);
    }
public function logUnclassifiedEmail(Request $request)
{
    // Capturamos el correo ya sea que venga como 'correo' o como 'remitente'
    $emailReal = $request->input('correo') ?? $request->input('remitente');

    // Si ambos vienen vacíos, ponemos un correo de respaldo para que la base de datos no estalle
    if (!$emailReal) {
        $emailReal = 'desconocido@latinpyme.com';
    }

    // Buscamos la primera cuenta corporativa que exista en el sistema
    $companyEmailId = \App\Models\CompanyEmail::value('id') ?? 1;

    // Guardamos usando estrictamente las llaves de tu $fillable
    \App\Models\EmailRule::create([
        'user_id'             => 1,
        'company_email_id'    => $companyEmailId,
        'correo'              => $emailReal, // Usamos la variable segura
        'carpeta'             => 'POR CLASIFICAR',
        'asunto'              => $request->input('asunto') ?? '(Sin Asunto)',
        'observaciones'       => 'Registrado automáticamente por n8n.',
        'carpeta_sugerida'    => 'POR CLASIFICAR',
        'confirma_sugerencia' => 'No',
        'carpeta_elegida'     => 'POR CLASIFICAR',
        'id_mensaje'          => $request->input('id_mensaje'),
        'tipo_filtro'         => 'Remitente',
        'valor_filtro'        => $emailReal,
        'estado'              => 'Activo',
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Registrado con éxito saltando validaciones vacías.'
    ], 200);
}}
