<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletedEmail extends Model
{
    protected $fillable = [
        'user_id',
        'correo_remitente',
        'asunto',
        'id_mensaje',
        'fecha_eliminacion',
    ];
}
