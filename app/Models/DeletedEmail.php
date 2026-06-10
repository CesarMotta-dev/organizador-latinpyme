<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'correo_remitente', // ◄— Nombre real en tu migración
        'asunto',
        'id_mensaje',
        'fecha_eliminacion'
    ];
}
