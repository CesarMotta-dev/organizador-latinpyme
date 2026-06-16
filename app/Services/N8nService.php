<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class N8nService
{
    protected string $webhookUrl;
    protected string $secret;

    public function __construct()
    {
        $this->webhookUrl = config('services.n8n.webhook_url', '');
        $this->secret     = config('services.n8n.webhook_secret', '');
    }

    /**
     * Envía las reglas del usuario al webhook de n8n.
     *
     * @param  int    $userId   ID del usuario dueño de las reglas
     * @param  array  $reglas   Colección de reglas (array o Collection)
     * @return bool   true si n8n respondió 2xx, false en caso de error
     */
    public function syncRules(int $userId, $reglas): bool
    {
        if (empty($this->webhookUrl)) {
            Log::warning('[N8n] N8N_API_URL no está configurada en el .env');
            return false;
        }

        try {
            $payload = [
                'user_id' => $userId,
                'reglas'  => is_array($reglas) ? $reglas : $reglas->toArray(),
            ];

            $response = Http::withHeaders([
                'X-N8N-Secret'  => $this->secret,
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
                'ngrok-skip-browser-warning' => 'true', // 🚀 REQUISITO NGROK: Evita que bloquee la petición con su pantalla de advertencia
            ])
            ->timeout(15)
            ->post($this->webhookUrl, $payload);

            if ($response->successful()) {
                Log::info('[N8n] Reglas sincronizadas correctamente', [
                    'user_id'     => $userId,
                    'total_reglas' => count($payload['reglas']),
                    'status'      => $response->status(),
                ]);
                return true;
            }

            Log::error('[N8n] Respuesta no exitosa del webhook', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            return false;

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[N8n] No se pudo conectar al webhook: ' . $e->getMessage());
            return false;
        } catch (\Exception $e) {
            Log::error('[N8n] Error inesperado: ' . $e->getMessage());
            return false;
        }
    }
}
