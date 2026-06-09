<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\Model;

use App\Models\CompanyEmail;

use Illuminate\Http\Request;

class EmailRule extends Model
{
    use HasFactory;

    // Los campos que permitimos guardar masivamente
    protected $fillable = [
        'user_id',
        'company_email_id',
        'correo',
        'carpeta',
        'asunto',
        'observaciones',
        'carpeta_sugerida',
        'confirma_sugerencia',
        'carpeta_elegida',
        'id_mensaje',
        'tipo_filtro',
        'valor_filtro',
        'estado',
    ];

    // Relación: Una regla pertenece a un usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Una regla pertenece a un correo empresarial
    public function companyEmail()
    {
        return $this->belongsTo(CompanyEmail::class);
    }
public function logUnclassifiedEmail(Request $request)
{
    // 1. Validamos usando tus campos reales del $fillable
    $request->validate([
        'correo' => 'required|string',
        'asunto' => 'nullable|string',
        'id_mensaje' => 'nullable|string',
    ]);

    // 2. Buscamos el ID de la cuenta corporativa si viene en el request
    $companyEmailId = null;
    if ($request->filled('company_email')) {
        $companyEmailId = \App\Models\CompanyEmail::where('email', $request->company_email)->value('id');
    }

    // Si no lo encuentra o no viene, asígnale el ID de la cuenta que estás viendo en tu Dashboard (ej: 1)
    if (!$companyEmailId) {
        $companyEmailId = 1;
    }

    // 3. Guardamos usando estrictamente las llaves de tu $fillable para que Laravel NO bloquee nada
    \App\Models\EmailRule::create([
        'user_id'             => 1, // ID del administrador
        'company_email_id'    => $companyEmailId,
        'correo'              => $request->input('correo'), // <-- ¡Mismo nombre de tu $fillable!
        'carpeta'             => 'POR CLASIFICAR',
        'asunto'              => $request->input('asunto') ?? '(Sin Asunto)',
        'observaciones'       => 'Registrado automáticamente por n8n como desconocido.',
        'carpeta_sugerida'    => 'POR CLASIFICAR',
        'confirma_sugerencia' => 'No',
        'carpeta_elegida'     => 'POR CLASIFICAR',
        'id_mensaje'          => $request->input('id_mensaje'),
        'tipo_filtro'         => 'Remitente', // O el valor por defecto que use tu lógica
        'valor_filtro'        => $request->input('correo'),
        'estado'              => 'Activo', // Estado para saltar cualquier filtro de tu Vue
    ]);

    return response()->json([
        'success' => true,
        'message' => '¡Regla fantasma creada con éxito usando los campos reales!'
    ], 200);
}
}
