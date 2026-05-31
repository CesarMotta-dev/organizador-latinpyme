<?php

namespace App\Http\Controllers;

use App\Models\CompanyEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class CompanyEmailController extends Controller
{
    private $n8nWebhookUrl;

    public function __construct()
    {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url');
    }
    /**
     * Verifica que el usuario sea administrador (para métodos de gestión).
     */
    private function requireAdmin(): void
    {
        abort_if(!auth()->user()->isAdmin(), 403, 'Solo administradores pueden gestionar correos empresariales.');
    }

    /**
     * Display a listing of company emails.
     */
    public function index()
    {
        $this->requireAdmin();
        $companyEmails = auth()->user()->companyEmails()->latest()->get();

        return Inertia::render('CompanyEmails/Index', [
            'companyEmails' => $companyEmails
        ]);
    }

    /**
     * Show the form for creating a new company email.
     */
    public function create()
    {
        $this->requireAdmin();
        $workers = \App\Models\User::where('role', 'worker')->get(['id', 'name', 'email']);

        return Inertia::render('CompanyEmails/Create', [
            'workers' => $workers
        ]);
    }

    /**
     * Store a newly created company email in storage.
     */
    public function store(Request $request)
    {
        $this->requireAdmin();
        $request->validate([
            'empresa' => 'required|string|max:255',
            'email' => 'required|email|unique:company_emails',
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'service_account_key' => 'nullable|json',
            'worker_id' => 'nullable|exists:users,id',
        ]);

        $companyEmail = auth()->user()->companyEmails()->create([
            'empresa' => $request->empresa,
            'email' => $request->email,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'service_account_key' => $request->service_account_key ? json_decode($request->service_account_key, true) : null,
            'worker_id' => $request->worker_id,
            'estado' => 'Activo',
        ]);

        $this->dispatchN8nAccountEvent('cuenta_creada', $companyEmail);

        return redirect()->route('company-emails.index')->with('success', 'Correo empresarial registrado.');
    }

    /**
     * Show the form for editing the specified company email.
     */
    public function edit(CompanyEmail $companyEmail)
    {
        $this->requireAdmin();
        abort_if($companyEmail->user_id !== auth()->id(), 403);

        $workers = \App\Models\User::where('role', 'worker')->get(['id', 'name', 'email']);

        return Inertia::render('CompanyEmails/Edit', [
            'correoEmpresarial' => $companyEmail,
            'workers' => $workers
        ]);
    }

    /**
     * Update the specified company email in storage.
     */
    public function update(Request $request, CompanyEmail $companyEmail)
    {
        $this->requireAdmin();
        abort_if($companyEmail->user_id !== auth()->id(), 403);

        $request->validate([
            'empresa' => 'required|string|max:255',
            'email' => 'required|email|unique:company_emails,email,' . $companyEmail->id,
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string|max:1000',
            'service_account_key' => 'nullable|json',
            'worker_id' => 'nullable|exists:users,id',
            'estado' => 'required|in:Activo,Inactivo',
        ]);

        $companyEmail->update([
            'empresa' => $request->empresa,
            'email' => $request->email,
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'service_account_key' => $request->service_account_key ? json_decode($request->service_account_key, true) : null,
            'worker_id' => $request->worker_id,
            'estado' => $request->estado,
        ]);

        $this->dispatchN8nAccountEvent('cuenta_actualizada', $companyEmail);

        return redirect()->route('company-emails.index')->with('success', 'Correo empresarial actualizado.');
    }

    /**
     * Remove the specified company email from storage.
     */
    public function destroy(CompanyEmail $companyEmail)
    {
        $this->requireAdmin();
        abort_if($companyEmail->user_id !== auth()->id(), 403);

        $companyEmail->delete();

        return redirect()->route('company-emails.index')->with('success', 'Correo empresarial eliminado.');
    }

    /**
     * Get company emails for dropdown/select (API).
     */
    public function getForSelect()
    {
        $user = auth()->user();
        $query = $user->isAdmin() ? $user->companyEmails() : $user->assignedCompanyEmails();

        return response()->json(
            $query->where('estado', 'Activo')
                ->select('id', 'email', 'nombre')
                ->get()
        );
    }

    private function dispatchN8nAccountEvent(string $event, CompanyEmail $companyEmail): void
    {
        if (!$this->n8nWebhookUrl || !$companyEmail->service_account_key) {
            return;
        }

        try {
            Http::timeout(8)
                ->withHeaders(array_filter([
                    'X-N8N-Webhook-Secret' => config('services.n8n.webhook_secret'),
                ]))
                ->post($this->n8nWebhookUrl, [
                    'evento' => $event,
                    'cuenta_afectada' => $companyEmail->email,
                    'cuenta' => [
                        'id' => $companyEmail->id,
                        'email' => $companyEmail->email,
                        'empresa' => $companyEmail->empresa,
                        'nombre' => $companyEmail->nombre,
                        'worker_id' => $companyEmail->worker_id,
                        'service_account_key' => $companyEmail->service_account_key,
                    ],
                    'usuario' => [
                        'id' => auth()->id(),
                        'email' => auth()->user()?->email,
                        'role' => auth()->user()?->role,
                    ],
                ]);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
