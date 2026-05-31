<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}