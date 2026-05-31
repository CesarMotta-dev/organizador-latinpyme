<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CompanyEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'worker_id',
        'empresa',
        'email',
        'nombre',
        'descripcion',
        'service_account_key',
        'estado',
    ];

    protected $casts = [
        'service_account_key' => 'encrypted:array',
    ];

    // Relación: Un correo empresarial pertenece a un usuario (admin)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relación: Un correo empresarial tiene muchas reglas
    public function emailRules()
    {
        return $this->hasMany(EmailRule::class);
    }

    // Relación: Un correo empresarial puede estar asignado a un trabajador
    public function worker()
    {
        return $this->belongsTo(User::class, 'worker_id');
    }
}
