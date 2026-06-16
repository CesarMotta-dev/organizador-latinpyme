<?php

namespace App\Http\Controllers;

use App\Models\EmailRule;
use App\Models\CompanyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // 🚀 REQUISITO: Para disparar a la nube de Railway
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class EmailRuleController extends Controller
{
    /**
     * URL del Webhook almacenada en el .env
     */
    private $n8nWebhookUrl;

    /**
     * Constructor para inicializar la URL dinámicamente
     */
    public function __construct()
    {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url');
    }

    /**
     * Display the dashboard with email rules.
     */
    public function index()
    {
        $user = auth()->user();
        
        $companyEmails = $this->accessibleCompanyEmails()->where('estado', 'Activo')->get();
        $validCompanyEmailIds = $companyEmails->pluck('id')->toArray();

        // Detect and report broken email rule relationships
        $brokenRuleCount = EmailRule::where('user_id', auth()->id())
            ->where(function ($query) use ($validCompanyEmailIds) {
                $query->whereNull('company_email_id');

                if (!empty($validCompanyEmailIds)) {
                    $query->orWhereNotIn('company_email_id', $validCompanyEmailIds);
                }
            })
            ->count();

        // Build the query for the selected company email
        $query = EmailRule::whereIn('company_email_id', $validCompanyEmailIds);

        $selectedCompanyEmailId = request('company_email_id');
        $selectedCompanyEmailId = $selectedCompanyEmailId !== null ? (int) $selectedCompanyEmailId : null;

        if ($selectedCompanyEmailId && in_array($selectedCompanyEmailId, $validCompanyEmailIds, true)) {
            $query->where('company_email_id', $selectedCompanyEmailId);
        } elseif ($firstCompanyEmail = $companyEmails->first()) {
            $selectedCompanyEmailId = $firstCompanyEmail->id;
            $query->where('company_email_id', $firstCompanyEmail->id);
        }

        $misReglas = $query->latest()->get();

        return Inertia::render('Dashboard', [
            'reglasDB' => $misReglas,
            'companyEmails' => $companyEmails,
            'selectedCompanyEmailId' => $selectedCompanyEmailId,
            'brokenRules' => $brokenRuleCount,
            'currentUserEmail' => $user->email,
            'currentUserName' => $user->name,
            'userRole' => $user->role,
        ]);
    }

    /**
     * Store a newly created email rule in storage.
     */
    public function store(Request $request)
    {
        $companyEmailId = $this->resolveCompanyEmailId($request);
        abort_if(!$companyEmailId, 422, 'Debes seleccionar o especificar un correo empresarial válido.');

        // Validamos que los datos vengan correctos
        $request->validate([
            'company_email_id' => 'nullable|exists:company_emails,id',
            'company_email' => 'nullable|email',
            'correo' => [
                'nullable',
                'email',
                Rule::unique('email_rules')->where(fn ($query) => $query->where('company_email_id', $companyEmailId))
            ],
            'carpeta' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (strtolower(trim($value)) === 'inbox') {
                        $fail('La carpeta destino no puede ser "INBOX" ya que el objetivo es organizar el correo.');
                    }
                }
            ],
            'asunto' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'carpeta_sugerida' => 'nullable|string|max:255',
            'confirma_sugerencia' => 'nullable|string|max:50',
            'carpeta_elegida' => 'nullable|string|max:255',
            'id_mensaje' => 'nullable|string|max:255',
            'tipo_filtro' => 'nullable|in:remitente,asunto,contenido',
            'valor_filtro' => 'nullable|string|max:255',
        ]);

        $companyEmail = CompanyEmail::findOrFail($companyEmailId);

        $nuevaRegla = $companyEmail->emailRules()->create([
            'user_id' => auth()->id(),
            'correo' => $request->correo,
            'carpeta' => $request->carpeta,
            'asunto' => $request->asunto,
            'observaciones' => $request->observaciones,
            'carpeta_sugerida' => $request->carpeta_sugerida,
            'confirma_sugerencia' => $request->confirma_sugerencia,
            'carpeta_elegida' => $request->carpeta_elegida,
            'id_mensaje' => $request->id_mensaje,
            'tipo_filtro' => $request->tipo_filtro,
            'valor_filtro' => $request->valor_filtro,
            'estado' => 'Activo',
        ]);

        // 🚀 n8n TRIGGER: Avisamos que se creó una regla y mandamos a qué cuenta pertenece
        $this->dispatchN8nEvent('regla_creada', $companyEmail, [
            'regla' => $nuevaRegla,
        ]);
        return back();
    }

    /**
     * Import email rules from a CSV text.
     */
    /**
     * Import email rules from a CSV text.
     */
    public function import(Request $request)
    {
        $request->validate([
            'csv' => 'required|string',
            'company_email_id' => 'nullable|integer|exists:company_emails,id',
        ]);

        $csv = trim($request->csv);
        $lines = array_filter(array_map('trim', preg_split('/\r?\n/', $csv)));

        if (empty($lines)) {
            return back()->with('error', 'CSV vacío');
        }

        $user = auth()->user();
        $companyEmails = $this->accessibleCompanyEmails()->where('estado', 'Activo')->get();
        $selectedCompanyEmail = null;

        if ($request->filled('company_email_id')) {
            $selectedCompanyEmail = $companyEmails->firstWhere('id', (int) $request->company_email_id);
        }

        $selectedCompanyEmail ??= $companyEmails->first();

        if (!$selectedCompanyEmail) {
            return back()->withErrors([
                'csv' => $user->isAdmin()
                    ? 'Crea una cuenta empresarial activa antes de importar reglas.'
                    : 'No tienes una cuenta empresarial activa asignada para importar reglas.',
            ]);
        }

        $headerLine = array_shift($lines);
        $delimiter = $this->detectCsvDelimiter($headerLine);
        $headers = str_getcsv($headerLine, $delimiter);
        $headers = array_map(fn ($header) => $this->normalizeImportHeader($header), $headers);

        $map = [
            'CUENTA_ORIGEN' => 'company_email', // 🚀 NUEVO: Correo corporativo administrador (ej: gerencia@latinpymes.com)
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

        $reglasImportadas = [];
        $filasOmitidas = 0;

        foreach ($lines as $line) {
            $row = str_getcsv($line, $delimiter);
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

            // Validamos que venga el correo remitente/filtro
            if (empty($data['correo']) || !filter_var($data['correo'], FILTER_VALIDATE_EMAIL)) {
                $filasOmitidas++;
                continue;
            }

            // No permitimos que la carpeta destino sea INBOX
            if (isset($data['carpeta']) && strtolower(trim($data['carpeta'])) === 'inbox') {
                $filasOmitidas++;
                continue;
            }

            // 🚀 CONTROL CENTRALIZADO: Buscamos a qué cuenta corporativa pertenece la regla en el CSV
            // Si no se especifica en el CSV, le asignamos la primera cuenta activa del admin logueado
            // Evitamos llamar a user() en el helper auth() para no romper análisis estático
            $companyEmail = $selectedCompanyEmail;
            if (!empty($data['company_email']) && filter_var($data['company_email'], FILTER_VALIDATE_EMAIL)) {
                $companyEmail = $companyEmails->firstWhere('email', $data['company_email']);

                if (!$companyEmail && $user->isAdmin()) {
                    $companyEmail = $this->findOrCreateCompanyEmail($data['company_email']);
                    if ($companyEmail) {
                        $companyEmails->push($companyEmail);
                    }
                }
            }

            $companyEmailId = $companyEmail?->id;
            $adminEmails = [];

            if (!$companyEmailId) {
                $companyEmailId = reset($adminEmails); // Toma la primera por defecto si no viene especificada
            }

            if (!$companyEmailId) {
                continue; // Si el administrador no tiene cuentas asignadas, salta la línea
            }

            $companyEmailId = $companyEmail?->id;

            unset($data['company_email']);

            // 🚀 CORRECCIÓN DEL ERROR: Ahora se guarda a través del modelo CompanyEmail encontrado
            $companyEmail = CompanyEmail::find($companyEmailId);
            if (!$companyEmail) {
                continue;
            }

            $regla = $companyEmail->emailRules()->updateOrCreate(
                ['correo' => $data['correo']],
                array_merge($data, [
                    'user_id' => auth()->id(),
                    'estado' => 'Activo'
                ])
            );

            // Guardamos para n8n incluyendo los datos del dueño corporativo
            $regla->cuenta_afectada = $companyEmail->email;
            $reglasImportadas[] = $regla;
        }

        // 🚀 n8n TRIGGER MASIVO: Enviamos el lote completo cargado a Railway
        if (empty($reglasImportadas)) {
            return back()->withErrors([
                'csv' => 'No se importo ninguna fila. Revisa que CORREOS tenga emails validos y que el colaborador tenga una cuenta empresarial asignada.',
            ]);
        }

        $this->dispatchN8nEvent('importacion_masiva', $selectedCompanyEmail, [
            'cantidad' => count($reglasImportadas),
            'filas_omitidas' => $filasOmitidas,
            'reglas' => $reglasImportadas,
        ]);

        return back()->with('success', 'Importadas ' . count($reglasImportadas) . ' regla(s). Omitidas: ' . $filasOmitidas . '.');
    }
    /**
     * Update the specified email rule in storage.
     */
    public function update(Request $request, EmailRule $rule)
    {
        $companyEmailId = $this->resolveCompanyEmailId($request);
        abort_if(!$companyEmailId, 422, 'Debes seleccionar o especificar un correo empresarial válido.');

        $request->validate([
            'company_email_id' => 'nullable|exists:company_emails,id',
            'company_email' => 'nullable|email',
            'correo' => [
                'nullable',
                'email',
                Rule::unique('email_rules')->where(fn ($query) => $query->where('company_email_id', $companyEmailId))->ignore($rule->id)
            ],
            'carpeta' => [
                'required',
                'string',
                'max:255',
                function ($attribute, $value, $fail) {
                    if (strtolower(trim($value)) === 'inbox') {
                        $fail('La carpeta destino no puede ser "INBOX" ya que el objetivo es organizar el correo.');
                    }
                }
            ],
            'asunto' => 'nullable|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
            'carpeta_sugerida' => 'nullable|string|max:255',
            'confirma_sugerencia' => 'nullable|string|max:50',
            'carpeta_elegida' => 'nullable|string|max:255',
            'id_mensaje' => 'nullable|string|max:255',
            'tipo_filtro' => 'nullable|in:remitente,asunto,contenido',
            'valor_filtro' => 'nullable|string|max:255',
        ]);

        $companyEmail = CompanyEmail::findOrFail($companyEmailId);
        
        $isAccessible = $this->accessibleCompanyEmails()->where('id', $companyEmailId)->exists();
        abort_if(!$isAccessible, 403);
        
        abort_if($rule->company_email_id !== $companyEmailId, 403);

        $rule->update([
            'company_email_id' => $companyEmailId,
            'correo' => $request->correo,
            'carpeta' => $request->carpeta,
            'asunto' => $request->asunto,
            'observaciones' => $request->observaciones,
            'carpeta_sugerida' => $request->carpeta_sugerida,
            'confirma_sugerencia' => $request->confirma_sugerencia,
            'carpeta_elegida' => $request->carpeta_elegida,
            'id_mensaje' => $request->id_mensaje,
            'tipo_filtro' => $request->tipo_filtro,
            'valor_filtro' => $request->valor_filtro,
        ]);

        // 🚀 n8n TRIGGER: Enviamos la alerta de actualización de la regla
        $this->dispatchN8nEvent('regla_actualizada', $companyEmail, [
            'regla' => $rule,
        ]);
        return back();
    }

    /**
     * Remove the specified email rule from storage.
     */
    public function destroy(EmailRule $rule)
    {
        $isAccessible = $this->accessibleCompanyEmails()->where('id', $rule->company_email_id)->exists();
        abort_if(!$isAccessible, 403);

        $ruleData = $rule->toArray();
        $rule->delete();

        $this->dispatchN8nEvent('regla_eliminada', $companyEmail, [
            'regla' => $ruleData,
        ]);

        return back()->with('success', 'Regla eliminada correctamente.');
    }

    /**
     * Remove multiple selected email rules.
     */
    public function destroyMultiple(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:email_rules,id'
        ]);

        if (empty($request->ids)) {
            return back();
        }

        $rules = EmailRule::whereIn('id', $request->ids)->get();
        if ($rules->isEmpty()) {
            return back();
        }

        $companyEmailId = $rules->first()->company_email_id;
        
        $isAccessible = $this->accessibleCompanyEmails()->where('id', $companyEmailId)->exists();
        abort_if(!$isAccessible, 403);

        EmailRule::whereIn('id', $request->ids)->delete();

        $this->dispatchN8nEvent('reglas_eliminadas_masivamente', $companyEmail, [
            'cantidad' => count($request->ids),
        ]);

        return back()->with('success', 'Reglas seleccionadas eliminadas correctamente.');
    }

    /**
     * Remove all email rules for the selected company email.
     */
    public function destroyAll(Request $request)
    {
        $companyEmailId = $this->resolveCompanyEmailId($request);
        abort_if(!$companyEmailId, 422, 'Debes seleccionar un correo empresarial válido.');

        $isAccessible = $this->accessibleCompanyEmails()->where('id', $companyEmailId)->exists();
        abort_if(!$isAccessible, 403);

        $companyEmail = CompanyEmail::findOrFail($companyEmailId);

        $count = $companyEmail->emailRules()->count();
        $companyEmail->emailRules()->delete();

        $this->dispatchN8nEvent('todas_reglas_eliminadas', $companyEmail, [
            'cantidad' => $count,
        ]);

        return back()->with('success', "Se eliminaron $count reglas correctamente.");
    }

    /**
     * Resolve the company email ID from the request.
     * If the selected company email id is missing, try to create one from the provided email.
     */
    private function resolveCompanyEmailId(Request $request): ?int
    {
        if ($request->filled('company_email_id')) {
            return $this->accessibleCompanyEmails()
                ->where('id', $request->company_email_id)
                ->value('id');
        }

        if ($request->filled('company_email')) {
            return $this->findOrCreateCompanyEmail($request->company_email)?->id;
        }

        return null;
    }

    private function accessibleCompanyEmails()
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return CompanyEmail::query();
        }

        return CompanyEmail::where(function ($q) use ($user) {
            $q->where('worker_id', $user->id)
              ->orWhere('user_id', $user->id);
        });
    }

    private function detectCsvDelimiter(string $line): string
    {
        $delimiters = ["," => 0, "\t" => 0, ";" => 0];

        foreach ($delimiters as $delimiter => $count) {
            $delimiters[$delimiter] = substr_count($line, $delimiter);
        }

        arsort($delimiters);

        return array_key_first($delimiters) ?: ',';
    }

    private function normalizeImportHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header);
        $header = strtoupper(trim($header));

        return preg_replace('/\s+/', ' ', $header);
    }

    private function dispatchN8nEvent(string $event, CompanyEmail $companyEmail, array $payload = []): void
    {
        if (!$this->n8nWebhookUrl) {
            return;
        }

        try {
            Http::timeout(8)
                ->withHeaders(array_filter([
                    'X-N8N-Webhook-Secret' => config('services.n8n.webhook_secret'),
                    'ngrok-skip-browser-warning' => 'true', // 🚀 REQUISITO NGROK: Evita que bloquee la petición con su pantalla de advertencia
                ]))
                ->post($this->n8nWebhookUrl, [
                    'evento' => $event,
                    'cuenta_afectada' => $companyEmail->email,
                    'cuenta' => [
                        'id' => $companyEmail->id,
                        'email' => $companyEmail->email,
                        'empresa' => $companyEmail->empresa,
                        'nombre' => $companyEmail->nombre,
                    ],
                    'usuario' => [
                        'id' => auth()->id(),
                        'email' => auth()->user()?->email,
                        'role' => auth()->user()?->role,
                    ],
                    'datos' => $payload,
                ]);
        } catch (\Exception $e) {
            report($e);
        }
    }

    private function findOrCreateCompanyEmail(string $email): ?CompanyEmail
    {
        $user = auth()->user();
        
        $companyEmail = $this->accessibleCompanyEmails()->where('email', $email)->first();

        if ($companyEmail) {
            if ($companyEmail->estado !== 'Activo') {
                $companyEmail->update(['estado' => 'Activo']);
            }

            return $companyEmail;
        }
        
        if (!$user->isAdmin()) {
            return null; // Trabajadores no pueden crear cuentas nuevas
        }

        $domain = explode('@', $email)[1] ?? $email;
        $domain = preg_replace('/^www\./', '', $domain);

        return CompanyEmail::create([
            'user_id' => $user->id,
            'email' => $email,
            'empresa' => $domain,
            'nombre' => 'Cuenta ' . $domain,
            'descripcion' => 'Creado automáticamente desde regla de correo.',
            'estado' => 'Activo',
        ]);
    }
}
